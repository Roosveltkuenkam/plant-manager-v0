# 🌱 Plant Manager v0

Application web de gestion de plantes d'intérieur.

## Fonctionnalités principales
- Inscription, connexion, gestion de profil utilisateur (avec photo)
- Ajout, modification, suppression de plantes (nom, espèce, date d'achat, photo, besoins en eau)
- Historique d'arrosage pour chaque plante
- Rappel automatique d'arrosage (badge visuel + notification email)
- Interface moderne, responsive, navigation par onglets

## Technologies utilisées
- **Frontend** : HTML, CSS, JavaScript vanilla
- **Backend** : PHP (API REST)
- **Base de données** : MySQL

## Installation
1. Clonez le projet dans votre dossier WAMP/XAMPP :
   ```
   git clone ...
   ```
2. Importez le fichier `sql/schema.sql` dans votre base MySQL.
3. Configurez l'envoi de mail (voir section plus bas).
4. Accédez à l'application via `http://localhost/plant-manager-v0/`

## Configuration de l'envoi de mail
- Configurez un serveur SMTP local (msmtp, sendmail...) ou un relais SMTP dans `php.ini`.
- Planifiez le script `api/send_notifications.php` pour l'envoi automatique (cron ou tâche planifiée Windows).

## Simuler un rappel d'arrosage
- Modifiez la colonne `last_watered` d'une plante dans la base pour une date ancienne.
- Rechargez l'application : un badge "À arroser" s'affichera.
- Lancez manuellement le script de notification pour tester l'envoi de mail :
  ```
  php api/send_notifications.php
  ```

## Structure du projet
```
plant-manager-v0/
├── api/
│   ├── api.php
│   ├── upload.php
│   └── send_notifications.php
├── css/
│   └── styles.css
├── js/
│   └── app.js
├── sql/
│   └── schema.sql
├── uploads/
│   └── ...
├── index.html
└── README.md
```

## Auteur
- Projet réalisé par KUENKAM TITSOP ROOSVELT

## Licence
Ce projet est open-source, libre d'utilisation et de modification.
