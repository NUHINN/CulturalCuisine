<?php
$hideFooter = $hideFooter ?? false;
?>
</main>

<?php if (!$hideFooter): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="footer-brand" href="homepage.php">
                <span class="brand-mark"><i class="fa-solid fa-bowl-food"></i></span>
                <span><?php echo APP_NAME; ?></span>
            </a>
            <p>Discover recipes through the stories, festivals, ingredients, and memories that make them meaningful.</p>
        </div>
        <div class="footer-links">
            <a href="search.php">Browse Recipes</a>
            <a href="Recipe.php">Manage Recipes</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="social-links" aria-label="Social links">
            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> Cultural Cuisine Explorer. All rights reserved.</div>
</footer>
<?php endif; ?>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="confirmDeleteTitle">Confirm deletion</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                This action cannot be undone. Do you want to delete this item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
