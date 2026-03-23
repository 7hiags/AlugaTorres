<?php
// Configuração da API Meteorológica
// Altere conforme seu ambiente

// Opção 1: API Local (desenvolvimento)
// define('WEATHER_API_BASE_URL', 'http://localhost:5000');

// Opção 2: PythonAnywhere (produção)
// Substitua 'seuusername' pelo seu username do PythonAnywhere
define('WEATHER_API_BASE_URL', 'https://seuusername.pythonanywhere.com');

// Endpoints disponíveis
define('WEATHER_API_CURRENT', WEATHER_API_BASE_URL . '/api/weather/current');
define('WEATHER_API_HOURLY', WEATHER_API_BASE_URL . '/api/weather/hourly');
define('WEATHER_API_DAILY', WEATHER_API_BASE_URL . '/api/weather/daily');

// Fallback para Open-Meteo quando API não disponível
define('USE_OPENMETEO_FALLBACK', true);
