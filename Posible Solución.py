import requests

url = "http://127.0.0.1:8000/api/validar-certificado"
files = {
    'cer': open('certificado.cer', 'rb'),
    'key': open('llave.key', 'rb'),
}
data = {
    'contrasena': '12345678'
}

response = requests.post(url, files=files, data=data)

if response.status_code == 200:
    resultado = response.json()
    print("✅ Resultado:", resultado)
else:
    print("❌ Error:", response.status_code, response.text)
