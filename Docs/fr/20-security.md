# 20. Sécurité et validation

Avant une mise en production, le projet hôte doit définir sa politique de sécurité.

Points importants :

- protéger `/admin/media` avec Symfony Security ;
- contrôler les droits de création/modification/suppression ;
- limiter la taille des uploads ;
- valider les MIME réellement détectés côté serveur ;
- ne pas faire confiance uniquement à l'extension ;
- interdire les formats dangereux ;
- contrôler les URL externes ;
- sécuriser les embeds ;
- prévoir CSRF ou une stratégie équivalente pour les mutations ;
- éviter l'exécution de fichiers uploadés.

Exemple de contrôle d'accès :

```yaml
security:
    access_control:
        - { path: '^/admin/media', roles: ROLE_ADMIN }
        - { path: '^/_neox/media', roles: ROLE_ADMIN }
```

Les restrictions `accept` HTML améliorent l'UX mais ne constituent jamais une validation de sécurité.

[← NeoxCrud](19-neox-crud.md) · [Extension →](21-extension.md)
