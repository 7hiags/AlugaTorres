import sys
import os

# Add the backend directory to the path
# IMPORTANT: Replace 'yourusername' with your actual PythonAnywhere username
path = '/home/yourusername/mysite/backend'
if path not in sys.path:
    sys.path.insert(0, path)

# Change to the backend directory for cache files
os.chdir('/home/yourusername/mysite/backend')

# Import the Flask app
from api_meteorologia import app as application
