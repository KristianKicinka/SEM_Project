import requests
import json

API_URL = 'http://localhost:8000/api/get-app-hashes'

parameters = {
    'app_name' : 'Messenger',
    'hash_types': ['JA3', 'JA3S']
}

response = requests.post(API_URL, json={'params':parameters})

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)