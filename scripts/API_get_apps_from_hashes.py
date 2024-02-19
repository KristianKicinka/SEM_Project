import requests
import json

API_URL = 'http://localhost:8000/api/get-apps-from-hashes'

hashes = [
    '9c815150ea821166faecf80757d8826a',
    'ad5c62c07b2d26dc3d58ff9bd78b1aa8',
    '9b02ebd3a43b62d825e1ac605b621dc8',
    '52dd94c936a196017072a95ee76c2e23'
]

auth_key = 'j92x4jy6FvaMGusAYUkeh28vB2sSw9'

parameters = {
    'auth_key' : auth_key,
    'hash_types' : json.dumps(['JA3']),
    'hashes': json.dumps(hashes),
}

response = requests.post(API_URL, data=parameters)
json_formatted = json.dumps(response.json(), indent=2)

print(json_formatted)