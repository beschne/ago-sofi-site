        </main>

        <footer>
            &copy; 2026 AG Orion &middot; <a href="/impressum-datenschutz">Impressum / Datenschutz</a>
        </footer>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="/js/map.js?v=<?= @filemtime(__DIR__ . '/../js/map.js') ?: '1' ?>"></script>
    <?= $extraFooter ?? '' ?>
</body>
</html>
