<!-- ============================================
     Component: Stats Card
     Usage: Pass $statTitle, $statValue, $statIcon, $statColor
     ============================================ -->
<div class="col-6 col-lg-3 mb-4">
    <div class="stats-card" style="--stat-color: <?php echo htmlspecialchars($statColor); ?>;">
        <div class="card-body">
            <div class="stats-icon-wrap"
                 style="background-color: <?php echo htmlspecialchars($statColor); ?>18;">
                <i class="bi <?php echo htmlspecialchars($statIcon); ?>"
                   style="color: <?php echo htmlspecialchars($statColor); ?>;"></i>
            </div>
            <div class="stats-info">
                <div class="stats-value"><?php echo htmlspecialchars((string)$statValue); ?></div>
                <div class="stats-label"><?php echo htmlspecialchars($statTitle); ?></div>
            </div>
        </div>
    </div>
</div>
