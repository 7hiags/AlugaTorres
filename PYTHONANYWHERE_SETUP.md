# Configuração PythonAnywhere para API Meteorológica

## Passo 1: Criar Conta no PythonAnywhere
1. Acesse: https://www.pythonanywhere.com
2. Crie uma conta gratuita (ou paga se precisar de mais recursos)
3. Anote seu username (ex: seunome)

## Passo 2: Upload dos Arquivos

### Opção A - Via Git (Recomendado)
```bash
# No PythonAnywhere Console (Bash):
cd ~
git clone https://github.com/seuusuario/AlugaTorres.git mysite
# Ou faça upload manual dos arquivos
```

### Opção B - Upload Manual
1. No PythonAnywhere, vá em **Files**
2. Crie a pasta: `/home/seuusername/mysite/backend/`
3. Faça upload destes arquivos:
   - `api_meteorologia.py`
   - `requirements.txt`
   - `pythonanywhere_wsgi.py` (renomeie para `wsgi.py` na pasta raiz do web app)

## Passo 3: Configurar Virtual Environment
```bash
# No PythonAnywhere Console (Bash):
cd /home/seuusername/mysite
mkvirtualenv --python=/usr/bin/python3.10 mysite-venv
pip install -r backend/requirements.txt
```

## Passo 4: Criar Web App
1. No PythonAnywhere, vá em **Web**
2. Clique em **"Add a new web app"**
3. Selecione **"Manual configuration"**
4. Selecione **Python 3.10**
5. Configure:
   - **Source code**: `/home/seuusername/mysite`
   - **Working directory**: `/home/seuusername/mysite/backend`
   - **WSGI configuration file**: Edite e aponte para o arquivo wsgi.py

## Passo 5: Configurar WSGI
Edite o arquivo `/var/www/seuusername_pythonanywhere_com_wsgi.py`:

```python
import sys
import os

# Add the backend directory to the path
path = '/home/seuusername/mysite/backend'
if path not in sys.path:
    sys.path.insert(0, path)

# Change to the backend directory for cache files
os.chdir('/home/seuusername/mysite/backend')

# Import the Flask app
from api_meteorologia import app as application
```

## Passo 6: Atualizar calendario.php
No seu arquivo `calendario.php` (no InfinityFree ou local), atualize a URL da API:

```javascript
// Antes (localhost):
const res = await fetch('http://localhost:5000/api/meteorologia/previsao');

// Depois (PythonAnywhere):
const res = await fetch('https://seuusername.pythonanywhere.com/api/weather/current');
// ou /api/weather/hourly ou /api/weather/daily
```

## Passo 7: Configurar Always-On Task (Opcional - Apenas Contas Pagas)
Para manter a API sempre ativa:
1. Vá em **Tasks**
2. Crie uma tarefa sempre ativa que chama um endpoint a cada 5 minutos:
```bash
curl -s https://seuusername.pythonanywhere.com/api/weather/current > /dev/null
```

## Endpoints Disponíveis
Após configurado, seus endpoints serão:
- `https://seuusername.pythonanywhere.com/api/weather/current`
- `https://seuusername.pythonanywhere.com/api/weather/hourly`
- `https://seuusername.pythonanywhere.com/api/weather/daily`

## Solução de Problemas

### Erro 502 Bad Gateway
- Verifique se o virtual environment está ativado
- Confirme que todas as dependências estão instaladas: `pip list`

### Cache não funciona
- O PythonAnywhere permite escrita em `/tmp/` e no diretório do app
- O cache será criado automaticamente em `backend/.cache.sqlite`

### CORS Errors
- O Flask-CORS já está configurado no `api_meteorologia.py`
- Se houver problemas, adicione seu domínio específico no CORS

## Limitações do Plano Gratuito
- A API "dorme" após alguns minutos de inatividade
- Na primeira requisição após inatividade, pode demorar 10-30 segundos para responder
- Limite de CPU diário
- Para produção, considere o plano pago ($5/mês) ou um VPS

## Alternativa: Usar Open-Meteo Diretamente
Se preferir não usar PythonAnywhere, o `calendario.php` já está configurado para usar a API pública do Open-Meteo como fallback quando a API local não está disponível.
