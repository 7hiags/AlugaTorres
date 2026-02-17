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

# Setup the Open-Meteo API client with cache and retry on error
cache_session = requests_cache.CachedSession('.cache', expire_after=3600)
retry_session = retry(cache_session, retries=5, backoff_factor=0.2)
openmeteo = openmeteo_requests.Client(session=retry_session)


def get_weather_data():
    """
    Busca dados meteorológicos da API Open-Meteo e retorna no formato padronizado
    """
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
        
        # Process current data
        current = response.Current()
        current_temp = current.Variables(0).Value()
        current_humidity = current.Variables(1).Value()
        current_wind = current.Variables(2).Value()
        
        # Process daily data
        daily = response.Daily()
        daily_temp_max = daily.Variables(0).ValuesAsNumpy()
        daily_temp_min = daily.Variables(1).ValuesAsNumpy()
        daily_weathercode = daily.Variables(2).ValuesAsNumpy()
        daily_wind = daily.Variables(3).ValuesAsNumpy()
        
        # Process hourly data for humidity
        hourly = response.Hourly()
        hourly_humidity = hourly.Variables(0).ValuesAsNumpy()
        hourly_times = pd.date_range(
            start=pd.to_datetime(hourly.Time() + response.UtcOffsetSeconds(), unit="s", utc=True),
            end=pd.to_datetime(hourly.TimeEnd() + response.UtcOffsetSeconds(), unit="s", utc=True),
            freq=pd.Timedelta(seconds=hourly.Interval()),
            inclusive="left"
        )
        
        # Mapeamento dos códigos WMO para descrições em português
        weather_codes_map = {
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
        
        # Mapeamento para ícones OpenWeatherMap
        icon_mapping = {
            0: "01d",
            1: "02d",
            2: "03d",
            3: "04d",
            45: "50d",
            48: "50d",
            51: "09d",
            53: "09d",
            55: "09d",
            56: "09d",
            57: "09d",
            61: "10d",
            63: "10d",
            65: "10d",
            66: "10d",
            67: "10d",
            71: "13d",
            73: "13d",
            75: "13d",
            77: "13d",
            80: "09d",
            81: "09d",
            82: "09d",
            85: "13d",
            86: "13d",
            95: "11d",
            96: "11d",
            99: "11d"
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
            weather_desc = weather_codes_map.get(weather_code, "condições desconhecidas")
            icon_code = icon_mapping.get(weather_code, "01d")
            
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
                "icone": f"http://openweathermap.org/img/wn/{icon_code}@2x.png",
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


@app.route('/api/meteorologia/previsao', methods=['GET'])
def get_previsao():
    """
    Endpoint que retorna a previsão meteorológica para Torres Novas
    """
    data = get_weather_data()
    return jsonify(data)


@app.route('/api/meteorologia/atual', methods=['GET'])
def get_atual():
    """
    Endpoint que retorna apenas os dados meteorológicos atuais
    """
    data = get_weather_data()
    if data["success"]:
        return jsonify({
            "success": True,
            "cidade": data["cidade"],
            "pais": data["pais"],
            "atual": data["atual"]
        })
    return jsonify(data)


@app.route('/health', methods=['GET'])
def health_check():
    """
    Endpoint de health check para verificar se o servidor está rodando
    """
    return jsonify({
        "status": "ok",
        "servico": "API Meteorologia AlugaTorres",
        "timestamp": datetime.now().isoformat()
    })


@app.route('/', methods=['GET'])
def root():
    """
    Endpoint raiz com informações da API
    """
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
    
    # Iniciar servidor Flask
    app.run(host='0.0.0.0', port=5000, debug=False)
