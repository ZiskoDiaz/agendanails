# 💅 Agenda Nails  
Sistema de Gestión para Salones de Belleza / Nails

**Agenda Nails** es una aplicación web desarrollada en PHP que permite gestionar de forma centralizada la agenda, clientes, servicios, insumos, proveedores y usuarios de un salón de belleza o estudio de uñas.  
Está pensada para uso interno, con autenticación y panel administrativo.

---

## ✨ Funcionalidades

- 📅 Gestión de agenda y citas
- 👩‍💼 Administración de clientas
- 💅 Gestión de servicios
- 📦 Control de insumos y stock
- 🚚 Registro de proveedores
- 👥 Gestión de usuarios y roles
- 🔐 Sistema de autenticación (login / logout)
- 📊 Dashboard con métricas clave

---

## 🛠️ Stack Tecnológico

### Back-End
- PHP (vanilla)
- MySQL / MariaDB
- SQL

### Front-End
- HTML5
- CSS3
- JavaScript
- Diseño responsive (UI administrativa)

### Infraestructura
- Servidor Apache
- Entorno LAMP / XAMPP / similar

---

## 🏗️ Arquitectura

El proyecto utiliza una **arquitectura modular**, organizada por dominio funcional.  
Cada módulo gestiona su propia lógica y vistas, facilitando el mantenimiento y la escalabilidad.

```text
agenda-nails/
├── agenda/ # Gestión de citas
├── clientes/ # Módulo de clientas
├── servicios/ # Servicios del salón
├── insumos/ # Control de stock
├── proveedores/ # Proveedores
├── usuarios/ # Usuarios y roles
│
├── assets/ # CSS, JS, imágenes
├── config/ # Configuración y conexión DB
├── includes/ # Headers, footers, helpers
│
├── auth.php # Protección de rutas
├── login.php # Inicio de sesión
├── logout.php # Cierre de sesión
├── crear_admin.php # Creación de usuario admin
├── index.php # Dashboard principal
├── database.sql # Estructura de la base de datos
└── README.md
```

---

## 🔐 Autenticación y Seguridad

- Sistema de login con control de sesión
- Protección de rutas mediante `auth.php`
- Separación de usuarios y roles (ADMIN)

---

## ⚙️ Instalación

### Requisitos
- PHP 8+
- MySQL / MariaDB
- Servidor Apache
- Navegador web moderno

### Pasos

1. Clonar el repositorio

git clone https://github.com/tuusuario/agenda-nails.git
Importar la base de datos

Crear una base de datos en MySQL

Importar el archivo:

text
Copiar código
database.sql
Configurar la conexión

Editar los datos de conexión en:

text
Copiar código
config/
Crear usuario administrador

text
Copiar código
crear_admin.php
Acceder al sistema

text
Copiar código
http://localhost/agenda-nails/login.php
📊 Dashboard
El panel principal muestra:

Clientas activas

Citas del día

Citas pendientes

Alertas de stock bajo

Accesos rápidos a acciones frecuentes

🧩 Roadmap (Ideas Futuras)
 Estados de citas (confirmada / cancelada)

 Reportes mensuales

 Notificaciones

 Roles avanzados

 Exportación de datos

 Historial de clientas

📄 Licencia
Proyecto de uso privado / educativo.
La licencia puede definirse según el destino final del sistema.

👤 Autor
Francisco Díaz

GitHub: https://github.com/ZiskoDiaz

LinkedIn: https://www.linkedin.com/in/franciscodiazdev/
