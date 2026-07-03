Fonctionnalités ajoutées

- Authentification par sessions (login/logout)
- Permissions par rôles (`admin`, `editor`, `author`, `guest`)
- Filtres par rôle et date, tri des colonnes
- Export CSV (`export.php`) et export XLSX (`export_xlsx.php`) si PhpSpreadsheet installé
- Edition en masse (bulk edit) pour changer le rôle
- Protection CSRF sur formulaires et journaux d'audit (`audit_logs`)

Comptes de test (après import de `database.sql`):

- admin@example.com / admin123 (role: admin)
- editor@example.com / editor123 (role: editor)
- author@example.com / author123 (role: author)
- guest@example.com / guest123 (role: guest)

Étapes d'activation de l'export XLSX et migration audit:

1. Installer les dépendances Composer (PhpSpreadsheet):

```bash
cd tp-crud-php
composer require phpoffice/phpspreadsheet
```

2. Créer la table d'audit (exécuter la migration SQL):

Importez `migrations/create_audit_table.sql` dans votre base de données (phpMyAdmin ou mysql CLI).

3. Assurez-vous que le dossier `vendor/` est accessible par le serveur web (après `composer install`).

4. Utilisation:
- Le lien "Export CSV" reste disponible pour tous.
- Si `vendor/autoload.php` existe, `export_xlsx.php` fournira un fichier .xlsx.
