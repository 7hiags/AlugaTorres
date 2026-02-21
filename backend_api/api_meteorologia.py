#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
API de Meteorologia para AlugaTorres
Servidor Flask que fornece dados meteorológicos de Torres Novas
"""

from flask import Flask, jsonify
from flask_cors import CORS
import openmeteo_requests
import pandas as pd
import requests_cache
from retry_requests import retry
from datetime import datetime

app = Flask(__name__)
CORS(app)  # Permitir requisições de qualquer origem

# Configurar o cliente da API Open-Meteo com cache e nova tentativa em caso de erro
cache_session = requests_cache.CachedSession('.cache', expire_after=3600)
retry_session = retry(cache_session, retries=5, backoff_factor=0.2)
openmeteo = openmeteo_requests.Client(session=retry_session)


# Busca dados meteorológicos da API Open-Meteo e retorna no formato padronizado
def get_weather_data():
    url = "https://api.open-meteo.com/v1/forecast"
    params = {
        "latitude": 39.4811,
        "longitude": -8.5394,
        "daily": ["temperature_2m_max", "temperature_2m_min", "weathercode", "windspeed_10m_max"],
        "hourly": ["relative_humidity_2m"],
        "current": ["temperature_2m", "relative_humidity_2m", "wind_speed_10m"],
        "timezone": "Europe/Lisbon",
        "forecast_days": 16
    }
    
    try:
        responses = openmeteo.weather_api(url, params=params)
        response = responses[0]
        
        # Processa os dados atuais
        current = response.Current()
        current_temp = current.Variables(0).Value()
        current_humidity = current.Variables(1).Value()
        current_wind = current.Variables(2).Value()
        
        # Processa dados diários
        daily = response.Daily()
        daily_temp_max = daily.Variables(0).ValuesAsNumpy()
        daily_temp_min = daily.Variables(1).ValuesAsNumpy()
        daily_weathercode = daily.Variables(2).ValuesAsNumpy()
        daily_wind = daily.Variables(3).ValuesAsNumpy()
        
        # Processa dados horários para calcular humidade média diária
        hourly = response.Hourly()
        hourly_humidity = hourly.Variables(0).ValuesAsNumpy()
        hourly_times = pd.date_range(
            start=pd.to_datetime(hourly.Time() + response.UtcOffsetSeconds(), unit="s", utc=True),
            end=pd.to_datetime(hourly.TimeEnd() + response.UtcOffsetSeconds(), unit="s", utc=True),
            freq=pd.Timedelta(seconds=hourly.Interval()),
            inclusive="left"
        )
        
        # Mapeamento completo dos códigos WMO (Organização Meteorológica Mundial)
        # Fonte: https://open-meteo.com/en/docs
        weather_codes_map = {
            # Céu limpo
            0: "Céu limpo",
            
            # Nuvens
            1: "Principalmente limpo",
            2: "Parcialmente nublado",
            3: "Nublado",
            
            # Nevoeiro
            45: "Nevoeiro",
            48: "Nevoeiro com geada",
            
            # Chuvisco
            51: "Chuvisco leve",
            53: "Chuvisco moderado",
            55: "Chuvisco intenso",
            56: "Chuvisco congelado leve",
            57: "Chuvisco congelado intenso",
            
            # Chuva
            61: "Chuva leve",
            63: "Chuva moderada",
            65: "Chuva forte",
            66: "Chuva congelada leve",
            67: "Chuva congelada forte",
            
            # Neve
            71: "Neve leve",
            73: "Neve moderada",
            75: "Neve forte",
            77: "Grãos de neve",
            
            # Aguaceiros
            80: "Aguaceiro leve",
            81: "Aguaceiro moderado",
            82: "Aguaceiro forte",
            85: "Aguaceiro de neve leve",
            86: "Aguaceiro de neve forte",
            
            # Trovoadas
            95: "Trovoada",
            96: "Trovoada com granizo leve",
            99: "Trovoada com granizo forte"
        }
        
        # Mapeamento para ícones OpenWeatherMap (dia/noite)
        icon_mapping_day = {
            0: "01d", 1: "02d", 2: "03d", 3: "04d",
            45: "50d", 48: "50d",
            51: "09d", 53: "09d", 55: "09d", 56: "09d", 57: "09d",
            61: "10d", 63: "10d", 65: "10d", 66: "10d", 67: "10d",
            71: "13d", 73: "13d", 75: "13d", 77: "13d",
            80: "09d", 81: "09d", 82: "09d",
            85: "13d", 86: "13d",
            95: "11d", 96: "11d", 99: "11d"
        }
        
        icon_mapping_night = {
            0: "01n", 1: "02n", 2: "03n", 3: "04n",
            45: "50n", 48: "50n",
            51: "09n", 53: "09n", 55: "09n", 56: "09n", 57: "09n",
            61: "10n", 63: "10n", 65: "10n", 66: "10n", 67: "10n",
            71: "13n", 73: "13n", 75: "13n", 77: "13n",
            80: "09n", 81: "09n", 82: "09n",
            85: "13n", 86: "13n",
            95: "11n", 96: "11n", 99: "11n"
        }
        
        # Cores sugeridas por condição climática (hexadecimal)
        weather_colors = {
            0: "#FFD700",   # Céu limpo - Dourado
            1: "#87CEEB",   # Principalmente limpo - Azul céu
            2: "#B0C4DE",   # Parcialmente nublado - Azul claro
            3: "#778899",   # Nublado - Cinza
            45: "#D3D3D3",  # Nevoeiro - Cinza claro
            48: "#C0C0C0",  # Nevoeiro com geada - Prateado
            51: "#ADD8E6",  # Chuvisco leve - Azul claro
            53: "#87CEFA",  # Chuvisco moderado
            55: "#4682B4",  # Chuvisco intenso - Azul aço
            56: "#E0FFFF",  # Chuvisco congelado
            57: "#AFEEEE",  # Chuvisco congelado intenso
            61: "#5F9EA0",  # Chuva leve
            63: "#1E90FF",  # Chuva moderada - Dodger blue
            65: "#0000CD",  # Chuva forte - Azul médio
            66: "#B0E0E6",  # Chuva congelada
            67: "#87CEFA",  # Chuva congelada forte
            71: "#FFFAFA",  # Neve leve - Snow
            73: "#F0F8FF",  # Neve moderada - Alice blue
            75: "#E6E6FA",  # Neve forte - Lavanda
            77: "#FFF0F5",  # Grãos de neve
            80: "#87CEEB",  # Aguaceiro leve
            81: "#4682B4",  # Aguaceiro moderado
            82: "#191970",  # Aguaceiro forte - Azul meia-noite
            85: "#F0F8FF",  # Aguaceiro de neve
            86: "#E6E6FA",  # Aguaceiro de neve forte
            95: "#4B0082",  # Trovoada - Índigo
            96: "#483D8B",  # Trovoada com granizo
            99: "#2F4F4F"   # Trovoada forte - Cinza ardósia
        }
        
        # Determinar se é dia ou noite (6h às 18h = dia)
        hora_atual = datetime.now().hour
        is_daytime = 6 <= hora_atual < 18
        
        # Cores de fundo do widget para dia/noite
        widget_colors = {
            "dia": {
                "background": "linear-gradient(135deg, #87CEEB 0%, #E0F6FF 100%)",
                "text": "#1a3a5c",
                "card": "rgba(255, 255, 255, 0.9)"
            },
            "noite": {
                "background": "linear-gradient(135deg, #191970 0%, #2F4F4F 100%)",
                "text": "#ffffff",
                "card": "rgba(255, 255, 255, 0.15)"
            }
        }


        
        # Dias da semana em português
        dias_semana = {
            0: "Segunda-feira",
            1: "Terça-feira",
            2: "Quarta-feira",
            3: "Quinta-feira",
            4: "Sexta-feira",
            5: "Sábado",
            6: "Domingo"
        }
        
        # Data de hoje
        hoje = datetime.now().strftime("%Y-%m-%d")
        
        # Gerar datas
        daily_times = pd.date_range(
            start=pd.to_datetime(daily.Time() + response.UtcOffsetSeconds(), unit="s", utc=True),
            end=pd.to_datetime(daily.TimeEnd() + response.UtcOffsetSeconds(), unit="s", utc=True),
            freq=pd.Timedelta(seconds=daily.Interval()),
            inclusive="left"
        )
        
        # Calcular humidade média para cada dia
        def calcular_humidade_media(data_str):
            valores_dia = []
            for j, time in enumerate(hourly_times):
                if time.strftime("%Y-%m-%d") == data_str:
                    valores_dia.append(hourly_humidity[j])
            if len(valores_dia) == 0:
                return int(current_humidity)
            return int(sum(valores_dia) / len(valores_dia))
        
        # Construir array de previsão
        previsao = []
        for i, date in enumerate(daily_times):
            data_str = date.strftime("%Y-%m-%d")
            weather_code = int(daily_weathercode[i])
            weather_desc = weather_codes_map.get(weather_code, "Condição desconhecida")
            
            # Escolher ícone baseado no período do dia (para previsões futuras, assume dia)
            if i == 0:
                icon_code = icon_mapping_day.get(weather_code, "01d") if is_daytime else icon_mapping_night.get(weather_code, "01n")
            else:
                icon_code = icon_mapping_day.get(weather_code, "01d")
            
            # Para previsões futuras, usar ícone genérico (sem d/n)
            icon_code_generic = icon_mapping_day.get(weather_code, "01d")[:-1]  # Remove 'd' do final
            
            dia_info = {
                "data": data_str,
                "dia_semana": dias_semana.get(date.weekday(), ""),
                "dia_semana_pt": dias_semana.get(date.weekday(), ""),
                "temperatura_minima": round(float(daily_temp_min[i]), 1),
                "temperatura_maxima": round(float(daily_temp_max[i]), 1),
                "temperatura_atual": round(float(current_temp), 1) if i == 0 else None,
                "temperatura_media": round((float(daily_temp_min[i]) + float(daily_temp_max[i])) / 2, 1),
                "descricao": weather_desc,
                "descricao_pt": weather_desc,
                "descricao_en": _get_english_description(weather_code),

                "icone": f"http://openweathermap.org/img/wn/{icon_code}@2x.png",
                "icone_dia": f"http://openweathermap.org/img/wn/{icon_mapping_day.get(weather_code, '01d')}@2x.png",
                "icone_noite": f"http://openweathermap.org/img/wn/{icon_mapping_night.get(weather_code, '01n')}@2x.png",
                "codigo_wmo": weather_code,
                "cor_clima": weather_colors.get(weather_code, "#808080"),
                "categoria": _get_weather_category(weather_code),

                "humidade_media": calcular_humidade_media(data_str),
                "vento": round(float(daily_wind[i]), 1),
                "vento_medio": round(float(daily_wind[i]), 1),
                "hoje": data_str == hoje,
                "numero_previsoes": 1
            }
            previsao.append(dia_info)
            
            
        return {
            "success": True,
            "cidade": "Torres Novas",
            "pais": "Portugal",
            "is_dia": is_daytime,
            "periodo": "dia" if is_daytime else "noite",
            "cores_widget": widget_colors["dia"] if is_daytime else widget_colors["noite"],
            "previsao": previsao,
            "atual": {
                "temperatura": round(float(current_temp), 1),
                "humidade": int(current_humidity),
                "vento": round(float(current_wind), 1)
            }
        }

        
    except Exception as e:
        return {
            "success": False,
            "error": str(e),
            "previsao": []
        }


# Retorna descrição em inglês do código WMO
def _get_english_description(code):
    english_map = {
        0: "Clear sky",
        1: "Mainly clear",
        2: "Partly cloudy",
        3: "Overcast",
        45: "Fog",
        48: "Depositing rime fog",
        51: "Light drizzle",
        53: "Moderate drizzle",
        55: "Dense drizzle",
        56: "Light freezing drizzle",
        57: "Dense freezing drizzle",
        61: "Slight rain",
        63: "Moderate rain",
        65: "Heavy rain",
        66: "Light freezing rain",
        67: "Heavy freezing rain",
        71: "Slight snow fall",
        73: "Moderate snow fall",
        75: "Heavy snow fall",
        77: "Snow grains",
        80: "Slight rain showers",
        81: "Moderate rain showers",
        82: "Violent rain showers",
        85: "Slight snow showers",
        86: "Heavy snow showers",
        95: "Thunderstorm",
        96: "Thunderstorm with slight hail",
        99: "Thunderstorm with heavy hail"
    }
    return english_map.get(code, "Unknown")


# Retorna categoria geral do clima
def _get_weather_category(code):
    if code == 0:
        return "ceu_limpo"
    elif code in [1, 2, 3]:
        return "nublado"
    elif code in [45, 48]:
        return "nevoeiro"
    elif code in [51, 53, 55, 56, 57]:
        return "chuvisco"
    elif code in [61, 63, 65, 66, 67]:
        return "chuva"
    elif code in [71, 73, 75, 77]:
        return "neve"
    elif code in [80, 81, 82]:
        return "aguaceiro"
    elif code in [85, 86]:
        return "aguaceiro_neve"
    elif code in [95, 96, 99]:
        return "trovoada"
    else:
        return "desconhecido"


# Endpoint que retorna a previsão meteorológica para Torres Novas
@app.route('/api/meteorologia/previsao', methods=['GET'])
def get_previsao():
    data = get_weather_data()
    return jsonify(data)


# Endpoint que retorna apenas os dados meteorológicos atuais
@app.route('/api/meteorologia/atual', methods=['GET'])
def get_atual():
    data = get_weather_data()
    if data["success"]:
        return jsonify({
            "success": True,
            "cidade": data["cidade"],
            "pais": data["pais"],
            "atual": data["atual"]
        })
    return jsonify(data)


# Endpoint que retorna status de saúde do servidor
@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({
        "status": "ok",
        "servico": "API Meteorologia AlugaTorres",
        "timestamp": datetime.now().isoformat()
    })


# Endpoint que retorna informações sobre a API
@app.route('/', methods=['GET'])
def root():
    return jsonify({
        "servico": "API Meteorologia AlugaTorres",
        "versao": "1.0.0",
        "endpoints": [
            "/api/meteorologia/previsao - Previsão completa (16 dias)",
            "/api/meteorologia/atual - Dados atuais apenas",
            "/health - Health check"
        ],
        "cidade": "Torres Novas",
        "pais": "Portugal"
    })

# Inicia o servidor Flask
if __name__ == '__main__':
    print("=" * 60)
    print("API de Meteorologia - AlugaTorres")
    print("=" * 60)
    print("Iniciando servidor Flask...")
    print("URL: http://localhost:5000")
    print("Endpoints:")
    print("  - http://localhost:5000/api/meteorologia/previsao")
    print("  - http://localhost:5000/api/meteorologia/atual")
    print("  - http://localhost:5000/health")
    print("=" * 60)
    print("Pressione CTRL+C para parar o servidor")
    print("=" * 60)
    
    # Inicia servidor Flask
    app.run(host='0.0.0.0', port=5000, debug=False)
