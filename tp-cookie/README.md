# TP Cookie - Magasin en Ligne

Un petit projet e-commerce PHP démontrant la gestion d'un panier d'achat persistant via les **cookies**.

## 🎯 Fonctionnalités

- **Catalogue de produits** : Affichage des produits stockés en base de données
- **Panier persistant** : Stockage du panier dans un cookie (durée : 30 jours par défaut)
- **Gestion du panier** :
  - Ajouter des produits
  - Consulter le panier
  - Supprimer/décrémenter des articles
  - Vider complètement le panier
- **Sécurité** : Protection CSRF via tokens

## 📁 Structure du Projet

| Fichier | Description |
|---------|-------------|
| `index.php` | Page d'accueil avec catalogue |
| `panier.php` | Affichage du panier d'achat |
| `ajouter.php` | Ajout de produits au panier |
| `supprimer.php` | Suppression/décrémentation d'articles |
| `vider.php` | Vidage du panier |
| `db.php` | Configuration et connexion à la base de données |
| `panier_utils.php` | Fonctions utilitaires pour la gestion du panier |
| `confirmation.php` | Page de confirmation d'action |
| `database.sql` | Script de création de la base de données |

## 🔧 Installation

### Prérequis
- PHP 7.4+
- MySQL/MariaDB
- XAMPP (ou serveur web local)

### Étapes
1. Placer le projet dans `htdocs/` (ou votre dossier web)
2. Créer la base de données `tp_cookie` :
   ```sql
   mysql -u root < database.sql
   ```
3. Configurer les identifiants de connexion dans `db.php` si nécessaire
4. Accéder via http://localhost/technologieweb/tp-cookie/

## 🍪 Fonctionnement des Cookies

- **Nom du cookie** : `panier`
- **Contenu** : JSON sérialisé avec les articles et quantités
- **Durée par défaut** : 30 jours
- **Mode démo d'expiration** : 30 secondes

## 📝 Utilisation

1. Naviguer vers la page d'accueil (`index.php`)
2. Consulter le catalogue de produits
3. Ajouter des articles au panier
4. Accéder au panier via le lien correspondant
5. Modifier les quantités ou supprimer des articles
6. Confirmer les actions

## 🔒 Sécurité

- Jetons CSRF intégrés pour la protection contre les attaques
- Validation des données en entrée
- Préparation des requêtes SQL (PDO)

## 📚 Licence

Projet pédagogique - Libre d'usage
