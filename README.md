 # Gestion de Bibliothèque - Projet Database
 mini projet web


Ce projet consiste en une base de données MySQL (`bibliotheque`) conçue pour gérer une bibliothèque, incluant des livres, des utilisateurs et des emprunts. Il est accompagné d'un dashboard PHP pour une interface utilisateur simple. Ce fichier `README` explique comment configurer et utiliser le projet.

## Prérequis

- Un serveur web avec PHP (version 7.4 ou supérieure recommandée).
- MySQL (version 5.7 ou supérieure recommandée).
- Accès à un outil de gestion de base de données (par exemple, phpMyAdmin, MySQL Workbench) ou à la ligne de commande MySQL.
- Un serveur local comme XAMPP, WAMP ou MAMP (facultatif, pour un environnement de développement local).

## Installation

### 1. Configuration de la base de données
- Téléchargez ou copiez le fichier `create_bibliotheque.sql` fourni avec ce projet.
- Exécutez le script SQL pour créer la base de données et insérer les données initiales :
  - **Via la ligne de commande** :
    ```bash
    mysql -u root -p < create_bibliotheque.sql
