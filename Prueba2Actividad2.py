import subprocess #Importamos modulo que permite ejecutar comandos del sistema operativo

ip = input("IP del dispositivo: ").strip() #Pedimos ip del switch, y recortamos espacios en blanco al inicio y al final del string

usuario = input("Usuario: ").strip() #Pedimos usuario del switch y se hace lo mismo que en ip

subprocess.run(["ssh", f"{usuario}@{ip}"]) #Corremos la conexion con los datos proporcionados