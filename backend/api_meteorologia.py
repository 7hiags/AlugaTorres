from flask import Flask, jsonify
from flask_cors import CORS
import requests
from datetime import datetime

# Bibliotecas adicionais para Open-Meteo com cache/retries e processamento
import requests_cache
from retry_requests import retry
import openmeteo_requests
import pandas as pd
import numpy as np

# Configurar sessão com cache e retry para evitar chamadas excessivas e melhorar resiliência
cache_session = requests_cache.CachedSession('.cache', expire_after=3600)
retry_session = retry(cache_session, retries=3, backoff_factor=0.2)
# Nota: openmeteo_requests oferece uma função helper; usaremos a API de alto nível
openmeteo_client = openmeteo_requests.Client(session=retry_session)

TORRES_NOVAS_COORDS = {"lat": 39.4811, "lon": -8.5394}
BASE_URL_FORECAST = "https://api.open-meteo.com/v1/forecast"

app = Flask(__name__)
CORS(app)

def traduzir_dia_semana(dia_en):
    traducoes = {
        "Monday": "Segunda-feira",
        "Tuesday": "Terça-feira",
        "Wednesday": "Quarta-feira",
        "Thursday": "Quinta-feira",
        "Friday": "Sexta-feira",
        "Saturday": "Sábado",
        "Sunday": "Domingo"
    }
    return traducoes.get(dia_en, dia_en)

# Mapeamento dos códigos WMO para descrições
WEATHER_CODES_MAP = {
    0: "céu limpo",
    1: "principalmente limpo",
    2: "parcialmente nublado",
    3: "nublado",
    45: "nevoeiro",
    48: "nevoeiro com geada",
    51: "chuvisco leve",
    53: "chuvisco moderado",
    55: "chuvisco intenso",
    56: "chuvisco congelado leve",
    57: "chuvisco congelado intenso",
    61: "chuva leve",
    63: "chuva moderada",
    65: "chuva forte",
    66: "chuva congelada leve",
    67: "chuva congelada forte",
    71: "neve leve",
    73: "neve moderada",
    75: "neve forte",
    77: "grãos de neve",
    80: "aguaceiro leve",
    81: "aguaceiro moderado",
    82: "aguaceiro forte",
    85: "neve em aguaceiro leve",
    86: "neve em aguaceiro forte",
    95: "trovoada",
    96: "trovoada com granizo leve",
    99: "trovoada com granizo forte"
}

# Mapeamento para ícones
ICON_MAPPING = {
    0: "01d", 1: "02d", 2: "03d", 3: "04d",
    45: "50d", 48: "50d", 51: "09d", 53: "09d", 55: "09d",
    56: "09d", 57: "09d", 61: "10d", 63: "10d", 65: "10d",
    66: "10d", 67: "10d", 71: "13d", 73: "13d", 75: "13d",
    77: "13d", 80: "09d", 81: "09d", 82: "09d",
    85: "13d", 86: "13d", 95: "11d", 96: "11d", 99: "11d"
}

# Endpoint para obter o clima atual de Torres Novas (usando Open-Meteo)
@app.route('/api/weather/hoje', methods=['GET'])
def clima_hoje():
    """Retorna o clima atual com humidade obtida preferencialmente de 'current' ou do horário mais próximo."""
    try:
        params = {
            "latitude": TORRES_NOVAS_COORDS['lat'],
            "longitude": TORRES_NOVAS_COORDS['lon'],
            "daily": ["temperature_2m_max", "temperature_2m_min", "weathercode", "windspeed_10m_max"],
            "hourly": ["relative_humidity_2m", "temperature_2m", "wind_speed_10m", "is_day"],
            "current": ["temperature_2m", "relative_humidity_2m", "is_day", "wind_speed_10m"],
            "forecast_hours": 1,
            "forecast_days": 1,
            "timezone": "Europe/Lisbon"
        }

        # Usar openmeteo_requests com cache/retry
        try:
            responses = openmeteo_requests.weather_api(BASE_URL_FORECAST, params=params)
            resp = responses[0]
        except Exception as e:
            # Fallback para requests direto em caso de erro com a lib
            resp = None

        temperatura_atual = None
        velocidade_vento = None
        weather_code = 0
        humidade = None

        if resp:
            try:
                curr = resp.Current()
                temperatura_atual = curr.Variables(0).Value() if curr and curr.Variables(0) else None
                humidade = curr.Variables(1).Value() if curr and curr.Variables(1) else None
                velocidade_vento = curr.Variables(3).Value() if curr and curr.Variables(3) else None
                # Algumas APIs não retornam weathercode no 'current' via esta lib; tentar pelo daily
                daily = resp.Daily()
                weathercode_arr = daily.Variables(2).ValuesAsNumpy() if daily and daily.Variables(2) else None
                if weathercode_arr is not None and len(weathercode_arr) > 0:
                    weather_code = int(weathercode_arr[0])
            except Exception:
                pass

            # Se humidade não esteve em current, procurar no horário mais próximo
            if humidade is None:
                try:
                    hourly = resp.Hourly()
                    rh = hourly.Variables(0).ValuesAsNumpy()
                    times = pd.date_range(start=pd.to_datetime(hourly.Time(), unit='s', utc=True),
                                           end=pd.to_datetime(hourly.TimeEnd(), unit='s', utc=True),
                                           freq=pd.Timedelta(seconds=hourly.Interval()), inclusive='left')
                    if len(rh) == len(times):
                        df = pd.DataFrame({'rh': rh}, index=times)
                        now = pd.Timestamp.now(tz='UTC')
                        closest = df.index.get_indexer([now], method='nearest')[0]
                        humidade = float(df.iloc[closest]['rh'])
                except Exception:
                    humidade = None

        # Fallback antigo usando requests (mantemos compatibilidade)
        if temperatura_atual is None or humidade is None:
            try:
                response = requests.get(BASE_URL_FORECAST, params={
                    "latitude": TORRES_NOVAS_COORDS['lat'],
                    "longitude": TORRES_NOVAS_COORDS['lon'],
                    "current_weather": True,
                    "hourly": "relativehumidity_2m",
                    "timezone": "Europe/Lisbon"
                }, timeout=10)
                data = response.json()
                if response.status_code == 200:
                    current = data.get('current_weather', {})
                    temperatura_atual = temperatura_atual or current.get('temperature')
                    velocidade_vento = velocidade_vento or current.get('windspeed')
                    # Encontrar humidade horária mais próxima
                    hourly = data.get('hourly', {})
                    times = hourly.get('time', [])
                    humidity_arr = hourly.get('relativehumidity_2m', [])
                    if times and humidity_arr:
                        try:
                            now = datetime.now()
                            closest_idx = None
                            min_diff = None
                            for idx, t in enumerate(times):
                                try:
                                    dt = datetime.fromisoformat(t)
                                except Exception:
                                    try:
                                        dt = datetime.strptime(t, '%Y-%m-%dT%H:%M')
                                    except Exception:
                                        continue
                                diff = abs((dt - now).total_seconds())
                                if min_diff is None or diff < min_diff:
                                    min_diff = diff
                                    closest_idx = idx
                            if closest_idx is not None and closest_idx < len(humidity_arr):
                                humidade = humidity_arr[closest_idx]
                            else:
                                humidade = humidity_arr[-1]
                        except Exception:
                            humidade = None
            except Exception:
                pass

        # Log e formatação final
        try:
            with open('weather_debug.log', 'a', encoding='utf-8') as f:
                f.write(f"[HOJE] {datetime.now().isoformat()} temp={temperatura_atual} hum={humidade} wind={velocidade_vento}\n")
        except Exception:
            pass

        weather_desc = WEATHER_CODES_MAP.get(weather_code, "condições desconhecidas")
        icon_code = ICON_MAPPING.get(weather_code, "01d")

        weather_data = {
            'temperatura_atual': temperatura_atual if temperatura_atual is not None else 0,
            'sensacao_termica': temperatura_atual if temperatura_atual is not None else 0,
            'temperatura_minima': temperatura_atual if temperatura_atual is not None else 0,
            'temperatura_maxima': temperatura_atual if temperatura_atual is not None else 0,
            'humidade': round(float(humidade), 1),
            'descricao': weather_desc.capitalize(),
            'descricao_pt': weather_desc.capitalize(),
            'icone': f"http://openweathermap.org/img/wn/{icon_code}@2x.png",
            'velocidade_vento': velocidade_vento if velocidade_vento is not None else 0,
            'nascer_do_sol': 'N/A',
            'por_do_sol': 'N/A',
            'cidade': 'Torres Novas',
            'data_consulta': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }

        return jsonify(weather_data)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

# Endpoint para obter a previsão do tempo para os próximos 16 dias
@app.route('/api/meteorologia/previsao', methods=['GET'])
def previsao_16_dias():
    """Retorna previsão para até 16 dias, calculando humidade média diária a partir das horas."""
    try:
        params = {
            "latitude": TORRES_NOVAS_COORDS['lat'],
            "longitude": TORRES_NOVAS_COORDS['lon'],
            "daily": ["temperature_2m_max", "temperature_2m_min", "weathercode", "windspeed_10m_max"],
            "hourly": ["relative_humidity_2m", "temperature_2m", "wind_speed_10m"],
            "forecast_days": 16,
            "timezone": "Europe/Lisbon"
        }

        try:
            responses = openmeteo_requests.weather_api(BASE_URL_FORECAST, params=params)
            resp = responses[0]
        except Exception as e:
            resp = None

        if resp:
            try:
                # Extrair daily
                daily = resp.Daily()
                dates = pd.to_datetime(daily.Time(), unit='s', utc=True).date
                temp_max = daily.Variables(0).ValuesAsNumpy() if daily and daily.Variables(0) else np.array([])
                temp_min = daily.Variables(1).ValuesAsNumpy() if daily and daily.Variables(1) else np.array([])
                weathercode = daily.Variables(2).ValuesAsNumpy() if daily and daily.Variables(2) else np.array([])
                wind_speed = daily.Variables(3).ValuesAsNumpy() if daily and daily.Variables(3) else np.array([])

                # Extrair hourly rh
                hourly = resp.Hourly()
                times = pd.date_range(start=pd.to_datetime(hourly.Time(), unit='s', utc=True),
                                      end=pd.to_datetime(hourly.TimeEnd(), unit='s', utc=True),
                                      freq=pd.Timedelta(seconds=hourly.Interval()), inclusive='left')
                rh = hourly.Variables(0).ValuesAsNumpy() if hourly and hourly.Variables(0) else np.array([])

                # Criar DataFrame horário e agregar média diária
                rh_series = pd.Series(data=rh, index=times)
                if not rh_series.empty:
                    rh_daily = rh_series.resample('D').mean().round(1)
                else:
                    rh_daily = pd.Series(dtype=float)

                resultado = []
                hoje = pd.Timestamp.now(tz='UTC').date()

                for i, d in enumerate(dates):
                    date_str = d.strftime('%Y-%m-%d')
                    hum_media = float(rh_daily.get(d, np.nan)) if d in rh_daily.index else 0
                    weather_desc = WEATHER_CODES_MAP.get(int(weathercode[i]) if i < len(weathercode) else 0, 'condições desconhecidas')
                    icon_code = ICON_MAPPING.get(int(weathercode[i]) if i < len(weathercode) else 0, '01d')

                    # Log humano
                    try:
                        with open('weather_debug.log', 'a', encoding='utf-8') as f:
                            f.write(f"[PREVISAO] {datetime.now().isoformat()} date={date_str} hum_media={hum_media}\n")
                    except Exception:
                        pass

                    dia_info = {
                        'data': date_str,
                        'dia_semana': d.strftime('%A'),
                        'dia_semana_pt': traduzir_dia_semana(d.strftime('%A')),
                        'temperatura_minima': float(temp_min[i]) if i < len(temp_min) else 0,
                        'temperatura_maxima': float(temp_max[i]) if i < len(temp_max) else 0,
                        'temperatura_media': round(((float(temp_min[i]) if i < len(temp_min) else 0) + (float(temp_max[i]) if i < len(temp_max) else 0)) / 2, 1),
                        'descricao': weather_desc.capitalize(),
                        'descricao_pt': weather_desc.capitalize(),
                        'icone': f"http://openweathermap.org/img/wn/{icon_code}@2x.png",
                        'humidade_media': float(hum_media) if not np.isnan(hum_media) else 0,
                        'vento_medio': float(wind_speed[i]) if i < len(wind_speed) else 0,
                        'hoje': date_str == hoje.strftime('%Y-%m-%d'),
                        'numero_previsoes': 1
                    }
                    resultado.append(dia_info)

                return jsonify({'previsao': resultado})

            except Exception as e:
                # Se algo falhar com openmeteo_requests, realizar fallback para requests simples
                print('Erro a processar resposta Open-Meteo via client:', e)

        # Fallback para implementação anterior usando requests
        # (mantemos a função já criada para compatibilidade)
        params_req = {
            "latitude": TORRES_NOVAS_COORDS['lat'],
            "longitude": TORRES_NOVAS_COORDS['lon'],
            "daily": ["temperature_2m_max", "temperature_2m_min", "weathercode", "windspeed_10m_max"],
            "hourly": "relativehumidity_2m",
            "forecast_days": 16,
            "timezone": "Europe/Lisbon"
        }
        response = requests.get(BASE_URL_FORECAST, params=params_req)
        if response.status_code != 200:
            data = response.json() if response.content else {}
            error_msg = data.get("reason", "Erro desconhecido")
            return jsonify({'error': f'Erro Open-Meteo: {error_msg}'}), 500

        previsao_final = processar_previsao_openmeteo(response.json())
        return jsonify({'previsao': previsao_final})

    except Exception as e:
        print(f"Error in previsao_16_dias: {str(e)}")
        import traceback
        traceback.print_exc()
        return jsonify({'error': f'Erro interno: {str(e)}'}), 500



def processar_previsao_openmeteo(data):
    """Processa dados da API Open-Meteo para 16 dias de previsão"""
    resultado = []
    hoje = datetime.now().date()

    # Extrai arrays de dados
    datas = data.get('daily', {}).get('time', [])
    temp_max = data.get('daily', {}).get('temperature_2m_max', [])
    temp_min = data.get('daily', {}).get('temperature_2m_min', [])
    weather_codes = data.get('daily', {}).get('weathercode', [])
    wind_speed = data.get('daily', {}).get('windspeed_10m_max', [])

    # Dados horários (para calcular humidade média diária)
    hourly_times = data.get('hourly', {}).get('time', [])
    hourly_hum = data.get('hourly', {}).get('relativehumidity_2m', [])

    # Processa cada dia
    for i in range(len(datas)):
        data_str = datas[i]
        data_obj = datetime.strptime(data_str, '%Y-%m-%d')

        # Mapeia weather code para descrição
        weather_desc = WEATHER_CODES_MAP.get(weather_codes[i], "condições desconhecidas") if i < len(weather_codes) else "Não disponível"
        icon_code = ICON_MAPPING.get(weather_codes[i], "01d") if i < len(weather_codes) else "01d"

        # Calcular humidade média para o dia (se houver dados horários)
        hum_media = 0
        try:
            if hourly_times and hourly_hum:
                # coletar valores horários que pertencem ao dia (assume formato ISO 'YYYY-MM-DD' no prefixo)
                hum_vals = [hourly_hum[j] for j, t in enumerate(hourly_times) if t.startswith(data_str)]
                if hum_vals:
                    hum_media = round(sum(hum_vals) / len(hum_vals), 1)
        except Exception:
            hum_media = 0

        # Log para debug de humidade diária
        try:
            with open('weather_debug.log', 'a', encoding='utf-8') as f:
                f.write(f"[PREVISAO] {datetime.now().isoformat()} date={data_str} hum_count={len(hum_vals) if 'hum_vals' in locals() else 0} hum_media={hum_media}\n")
        except Exception:
            pass

        # Cria objeto do dia
        dia_info = {
            'data': data_str,
            'dia_semana': data_obj.strftime('%A'),
            'dia_semana_pt': traduzir_dia_semana(data_obj.strftime('%A')),
            'temperatura_minima': temp_min[i] if i < len(temp_min) else 0,
            'temperatura_maxima': temp_max[i] if i < len(temp_max) else 0,
            'temperatura_media': round((temp_min[i] + temp_max[i]) / 2, 1) if i < len(temp_min) and i < len(temp_max) else 0,
            'descricao': weather_desc.capitalize(),
            'descricao_pt': weather_desc.capitalize(),
            'icone': f"http://openweathermap.org/img/wn/{icon_code}@2x.png",
            'humidade_media': hum_media,
            'vento_medio': wind_speed[i] if i < len(wind_speed) else 0,
            'hoje': data_str == hoje.strftime('%Y-%m-%d'),
            'numero_previsoes': 1  # Open-Meteo fornece dados diários agregados
        }
        resultado.append(dia_info)

    return resultado

if __name__ == '__main__':
    app.run(debug=True, port=5000, host='0.0.0.0')