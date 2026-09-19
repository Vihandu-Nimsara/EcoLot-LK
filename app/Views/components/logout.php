<form action="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8') ?>" method="post">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="logout-link">Logout</button>
</form>
