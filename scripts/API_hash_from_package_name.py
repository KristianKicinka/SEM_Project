import requests
import json

API_URL = 'http://localhost:8000/api/create-hash-from-package-name'

auth_key = 'wiZ68qvbifI3RHHZbtF6JZAIbzK78K'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
    'package_name': 'com.facebook.orca'
}

response = requests.post(API_URL, data=parameters)

print(response)

json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)