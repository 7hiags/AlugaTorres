import sys
import os

# Add the backend directory to the path
path = '/home/yourusername/AlugaTorres/backend'
if path not in sys.path:
    sys.path.insert(0, path)

# Import the Flask app
from api_meteorologia import app as application

# PythonAnywhere specific configuration
if __name__ == '__main__':
    application.run()