</div> <!-- End container -->

<?php if (is_logged_in()): ?>
<nav class="bottom-nav">
    <a href="/user/dashboard.php" class="<?php echo ($active_page == 'home') ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        Home
    </a>
    <a href="/user/contracts.php" class="<?php echo ($active_page == 'contracts') ? 'active' : ''; ?>">
        <i class="fas fa-file-contract"></i>
        Contracts
    </a>
    <a href="/user/team.php" class="<?php echo ($active_page == 'team') ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        Team
    </a>
    <a href="/user/wallet.php" class="<?php echo ($active_page == 'wallet') ? 'active' : ''; ?>">
        <i class="fas fa-wallet"></i>
        Wallet
    </a>
    <a href="/user/profile.php" class="<?php echo ($active_page == 'profile') ? 'active' : ''; ?>">
        <i class="fas fa-user"></i>
        Profile
    </a>
</nav>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
