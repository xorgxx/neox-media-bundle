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

## Sandbox for risky files

When the upload sandbox is enabled, files considered suspicious can be stored without being served publicly. This is the default behavior for formats such as SVG, PDF, and ZIP, which may contain active content or require human review.

The sandbox state is stored in `Media::metadata` (`sandboxed`, `risk_level`, `risk_reason`). It does not add Doctrine columns to `neox_media`, so already registered media remain visible without a schema migration.

[← NeoxCrud](19-neox-crud.md) · [Extension →](21-extension.md)
