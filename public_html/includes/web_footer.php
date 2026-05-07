<?php
/** @var array $data */
$negocio = $data['negocio'] ?? [];
$redes = $negocio['redes'] ?? [];
$footerCategorias = array_values(array_filter($data['categorias'] ?? [], fn($c) => (int)($c['footer'] ?? 1) === 1));
$footerPaginas = array_values(array_filter($data['paginas'] ?? [], fn($p) => (int)($p['footer'] ?? 0) === 1));
?>
</main>

<footer class="site-footer">
    <div class="footer-grid">
        <section>
            <h2><?= sw_e($negocio['nombre'] ?? 'Suave Urban Studio') ?></h2>
            <p><?= sw_e($negocio['footer_descripcion'] ?: ($negocio['descripcion'] ?? '')) ?></p>
            <div class="socials">
                <?php if (!empty($redes['tiktok'])): ?><a href="<?= sw_e($redes['tiktok']) ?>" target="_blank" rel="noopener">TikTok</a><?php endif; ?>
                <?php if (!empty($redes['facebook'])): ?><a href="<?= sw_e($redes['facebook']) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                <?php if (!empty($redes['instagram'])): ?><a href="<?= sw_e($redes['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
            </div>
        </section>

        <section>
            <h3>Colecciones</h3>
            <a href="/colecciones">Todas</a>
            <?php foreach ($footerCategorias as $cat): ?>
                <a href="/<?= sw_e($cat['slug'] ?? '') ?>"><?= sw_e($cat['nombre'] ?? 'Categoría') ?></a>
            <?php endforeach; ?>
        </section>

        <section>
            <h3>Atención</h3>
            <?php foreach ($footerPaginas as $page): ?>
                <a href="<?= sw_e($page['url'] ?? '#') ?>"><?= sw_e($page['titulo'] ?? 'Página') ?></a>
            <?php endforeach; ?>
        </section>

        <section>
            <h3>Contacto</h3>
            <?php if (!empty($negocio['correo'])): ?><p>✉ <?= sw_e($negocio['correo']) ?></p><?php endif; ?>
            <?php if (!empty($negocio['telefono'])): ?><p>☎ <?= sw_e($negocio['telefono']) ?></p><?php endif; ?>
            <?php if (!empty($negocio['whatsapp'])): ?><p><a href="<?= sw_e(sw_whatsapp_url($negocio)) ?>" target="_blank" rel="noopener">WhatsApp</a></p><?php endif; ?>
            <?php if (!empty($negocio['direccion'])): ?><p><?= nl2br(sw_e($negocio['direccion'])) ?></p><?php endif; ?>
        </section>
    </div>
    <div class="footer-bottom"><p><?= sw_e($negocio['copyright'] ?? ('© ' . date('Y') . ' Suave Urban Studio. Todos los derechos reservados.')) ?></p></div>
</footer>



<script src="/assets/js/suave-web-clean.js?v=<?= sw_e(SUAVE_WEB_VERSION) ?>" defer></script>
</body>
</html>
