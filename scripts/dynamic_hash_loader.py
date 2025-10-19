"""
 * @file dynamic_hash_loader.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Dynamic loading of custom hash types from database
"""

import json
import os
import sys
import importlib.util
import requests
from typing import Dict, List, Optional
from custom_hash_generators import CustomHashGenerator, CustomHashManager

class DatabaseHashLoader:
    """
    Loads custom hash types from database via Laravel API
    """
    
    def __init__(self, base_url: str = None, api_key: str = None):
        self.base_url = base_url or os.getenv('LARAVEL_BASE_URL', 'http://localhost:8000')
        self.api_key = api_key or os.getenv('LARAVEL_API_KEY')
        self.custom_hash_manager = CustomHashManager()
        self.session = requests.Session()
        if self.api_key:
            self.session.headers.update({'Authorization': f'Bearer {self.api_key}'})
        self.session.headers.update({'Content-Type': 'application/json'})
    
    def load_custom_hash_types(self, custom_hash_type_names: List[str] = None, use_manual_config: bool = False) -> Dict[str, CustomHashGenerator]:
        """
        Load custom hash types from database or manual configuration
        
        Args:
            custom_hash_type_names: List of custom hash type names to load
            use_manual_config: If True, use manual JSON configuration instead of database
            
        Returns:
            Dictionary with names and generators
        """
        if use_manual_config:
            print("Using manual configuration mode...")
            return self._load_from_manual_config(custom_hash_type_names)
        
        try:
            # Load from database via HTTP API
            db_config = self._load_from_database_api(custom_hash_type_names)
            
            generators = {}
            for config in db_config:
                generator = self._create_generator_from_db_config(config)
                if generator:
                    generators[generator.name] = generator
                    self.custom_hash_manager.register_generator(generator)
            
            return generators
            
        except Exception as e:
            print(f"Error loading custom hash types from database: {e}")
            print("Falling back to manual configuration...")
            return self._load_from_manual_config(custom_hash_type_names)
    
    def _load_from_database_api(self, custom_hash_type_names: List[str] = None) -> List[Dict]:
        """
        Load custom hash types from database via HTTP API
        
        Args:
            custom_hash_type_names: List of custom hash type names to load
            
        Returns:
            List of generator configurations
        """
        try:
            api_url = f"{self.base_url}/api/custom-hash-types/python-generator"
            
            # Prepare data for API
            data = {}
            if custom_hash_type_names:
                data['names'] = custom_hash_type_names
            
            # Make HTTP POST request
            response = self.session.post(api_url, json=data, timeout=10)
            response.raise_for_status()
            
            result = response.json()
            return result.get('generators', [])
            
        except requests.exceptions.RequestException as e:
            print(f"HTTP API request failed: {e}")
            raise
        except json.JSONDecodeError as e:
            print(f"Invalid JSON response: {e}")
            raise
        except Exception as e:
            print(f"Unexpected error in API call: {e}")
            raise
    
    def _load_from_manual_config(self, custom_hash_type_names: List[str] = None) -> Dict[str, CustomHashGenerator]:
        """
        Load custom hash types from manual JSON configuration file
        
        Args:
            custom_hash_type_names: List of custom hash type names to load
            
        Returns:
            Dictionary with names and generators
        """
        config_file = os.path.join(os.path.dirname(__file__), 'custom_hash_config.json')
        
        if not os.path.exists(config_file):
            print(f"Manual config file not found: {config_file}")
            return {}
        
        try:
            with open(config_file, 'r', encoding='utf-8') as f:
                config_data = json.load(f)
            
            generators_config = config_data.get('generators', [])
            
            # Filter by requested names if specified
            if custom_hash_type_names:
                generators_config = [g for g in generators_config if g.get('name') in custom_hash_type_names]
            
            generators = {}
            for config in generators_config:
                generator = self._create_generator_from_db_config(config)
                if generator:
                    generators[generator.name] = generator
                    self.custom_hash_manager.register_generator(generator)
            
            return generators
            
        except Exception as e:
            print(f"Error loading manual config file: {e}")
            return {}
    
    def _create_generator_from_db_config(self, config: Dict) -> Optional[CustomHashGenerator]:
        """
        Create generator from database configuration
        
        Args:
            config: Generator configuration dictionary
            
        Returns:
            CustomHashGenerator instance or None
        """
        try:
            generator_type = config.get("type")
            name = config.get("name")
            description = config.get("description", "")
            configuration = config.get("configuration", {})
            
            if generator_type == "simple_tls":
                fields = configuration.get("fields", [])
                return SimpleTLSHashGenerator(
                    name=name,
                    description=description,
                    fields=fields
                )
            elif generator_type == "custom_algorithm":
                algorithm = configuration.get("algorithm", "md5")
                fields = configuration.get("fields", [])
                return CustomAlgorithmHashGenerator(
                    name=name,
                    description=description,
                    algorithm=algorithm,
                    fields=fields
                )
            elif generator_type == "python_script":
                script_path = config.get("script_path")
                if script_path:
                    # For Python script, path is already absolute from database
                    full_script_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), script_path)
                    if os.path.exists(full_script_path):
                        return PythonScriptHashGenerator(
                            name=name,
                            description=description,
                            script_path=full_script_path
                        )
                    else:
                        print(f"Python script not found at: {full_script_path}")
            
            return None
            
        except Exception as e:
            print(f"Error creating generator from config: {e}")
            return None

# Import classes from custom_hash_generators
from custom_hash_generators import SimpleTLSHashGenerator, CustomAlgorithmHashGenerator, PythonScriptHashGenerator

# Global instance
database_hash_loader = DatabaseHashLoader()

def load_custom_hash_types_from_database(custom_hash_type_names: List[str] = None, use_manual_config: bool = False) -> Dict[str, CustomHashGenerator]:
    """
    Convenience function for loading custom hash types from database or manual config
    
    Args:
        custom_hash_type_names: List of custom hash type names to load
        use_manual_config: If True, use manual JSON configuration instead of database
        
    Returns:
        Dictionary with generators
    """
    return database_hash_loader.load_custom_hash_types(custom_hash_type_names, use_manual_config)

