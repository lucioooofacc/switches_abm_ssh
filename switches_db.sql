CREATE TABLE ubicaciones (
  id_ubicacion int NOT NULL AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  PRIMARY KEY (id_ubicacion),
  UNIQUE KEY nombre (nombre)
);

CREATE TABLE switches (
  id_switch int NOT NULL AUTO_INCREMENT,
  nombre varchar(30) NOT NULL,
  ip varchar(15) NOT NULL,
  modelo varchar(50) NOT NULL,
  id_ubicacion int NOT NULL,
  PRIMARY KEY (id_switch),
  UNIQUE KEY nombre (nombre),
  UNIQUE KEY ip (ip),
  CONSTRAINT FK_ubicaciones_switches FOREIGN KEY (id_ubicacion)
    REFERENCES ubicaciones (id_ubicacion)
);

CREATE TABLE responsables (
  id_responsable int NOT NULL AUTO_INCREMENT,
  documento varchar(8) NOT NULL,
  nombre_completo varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  telefono varchar(20) DEFAULT NULL,
  rol varchar(20) NOT NULL,
  PRIMARY KEY (id_responsable),
  UNIQUE KEY documento (documento),
  UNIQUE KEY email (email)
);

-- Resuelve la relación N:M entre responsables y switches.
-- Cada fila es un usuario y contraseña de un responsable en un switch.
CREATE TABLE credenciales (
  id_credencial int NOT NULL AUTO_INCREMENT,
  usuario varchar(30) NOT NULL,
  password varchar(100) NOT NULL,
  fecha_registro timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_switch int NOT NULL,
  id_responsable int NOT NULL,
  PRIMARY KEY (id_credencial),
  UNIQUE KEY switch_usuario (id_switch, usuario),
  CONSTRAINT FK_switches_credenciales FOREIGN KEY (id_switch)
    REFERENCES switches (id_switch),
  CONSTRAINT FK_responsables_credenciales FOREIGN KEY (id_responsable)
    REFERENCES responsables (id_responsable)
);

INSERT INTO ubicaciones (nombre) VALUES
 ('ITSP Salon 1'),
 ('ITSP Salon 2'),
 ('ITSP Salon 3');

INSERT INTO switches (nombre, ip, modelo, id_ubicacion) VALUES
 ('SW-1', '192.168.2.30', 'A confirmar', 1),
 ('SW-2', '192.168.2.31', 'A confirmar', 2),
 ('SW-3', '192.168.2.32', 'Cisco Catalyst 2960G-8TC-L', 3);

INSERT INTO responsables (documento, nombre_completo, email, telefono, rol) VALUES
 ('10000001', 'Leonel Frencia', 'leonelfrencia@gmail.com', '099000001', 'Administrador'),
 ('10000002', 'Juan Krauss', 'juankrauss@gmail.com', '099000002', 'Técnico'),
 ('10000003', 'Lucio Facchin', 'lucioofacchin@gmail.com', '099000003', 'Administrador');

INSERT INTO credenciales (usuario, password, id_switch, id_responsable) VALUES
 ('Ad1sw1', 'R1sw-1', 1, 1), ('Ad2sw1', 'R2sw-1', 1, 2), ('Ad3sw1', 'R3sw-1', 1, 3),
 ('Ad1sw2', 'R1sw-2', 2, 1), ('Ad2sw2', 'R2sw-2', 2, 2), ('Ad3sw2', 'R3sw-2', 2, 3),
 ('Ad1sw3', 'R1sw-3', 3, 1), ('Ad2sw3', 'R2sw-3', 3, 2), ('Ad3sw3', 'R3sw-3', 3, 3);
