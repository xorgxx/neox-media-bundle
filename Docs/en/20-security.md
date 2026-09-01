# 20. Security and Validation

Before going to production, the host project must define its security policy.

Important points:

- protect `/admin/media` with Symfony Security;
- control create/edit/delete permissions;
- limit upload size;
- validate actually detected MIME types server-side;
- do not trust the extension alone;
- forbid dangerous formats;
- control external URLs;
- secure embeds;
- provide CSRF or an equivalent strategy for mutations;
- prevent execution of uploaded files.

Access control example:

```yaml
security:
    access_control:
        - { path: '^/admin/media', roles: ROLE_ADMIN }
        - { path: '^/_neox/media', roles: ROLE_ADMIN }
```

HTML `accept` restrictions improve UX but never constitute a security validation.

[← NeoxCrud](19-neox-crud.md) · [Extension →](21-extension.md)
