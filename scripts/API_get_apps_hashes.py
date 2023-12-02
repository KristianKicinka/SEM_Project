import requests
import json

API_URL = 'http://localhost:8000/api/get-app-hashes'

apps = [
    {'package_name': 'com.facebook.orca', 'version': '', 'hash_types': ['JA3'] },
    {'package_name': 'com.whatsapp', 'version': '', 'hash_types': ['JA3'] }
]

auth_key = 'wiZ68qvbifI3RHHZbtF6JZAIbzK78K'

parameters = { 'apps': json.dumps(apps), 'auth_key' : auth_key }

response = requests.post(API_URL, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)