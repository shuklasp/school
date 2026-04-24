import json
import os
import mysql.connector

class SPP:
    _config = None
    _db = None

    @staticmethod
    def init(config_path=None):
        if config_path is None:
            # Detect bridge_config.json
            base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            config_path = os.path.join(base_dir, 'var', 'shared', 'bridge_config.json')
        
        if os.path.exists(config_path):
            with open(config_path, 'r') as f:
                SPP._config = json.load(f)
        else:
            raise Exception(f"SPP Bridge configuration not found at {config_path}")

    @staticmethod
    def db():
        if SPP._db is None:
            if SPP._config is None:
                SPP.init()
            
            db_conf = SPP._config.get('database', {})
            SPP._db = mysql.connector.connect(
                host=db_conf.get('dbhost'),
                user=db_conf.get('dbuser'),
                password=db_conf.get('dbpasswd'),
                database=db_conf.get('dbname')
            )
        return SPP._db

    @staticmethod
    def get_config(key, section='bridge_settings'):
        if SPP._config is None:
            SPP.init()
        return SPP._config.get(section, {}).get(key)

# Auto-initialize if config exists in default location
try:
    SPP.init()
except:
    pass
