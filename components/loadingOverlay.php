<?php
$loadingText = $loadingText ?? 'Loading assets (0%)';
$contentContainerClass = $contentContainerClass ?? 'content-container';
$contentHeading = $contentHeading ?? 'Growing Your Garden';
?>

<div id="loadingOverlay"
            class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center">
            <div class="d-flex flex-column justify-content-center align-items-center garden-overlay-content">
                <div class="text-center mb-4 content-container">
                    <?php getPlantImage('seed'); ?>
                    <?php getPlantImage('treelv2'); ?>
                    <?php getPlantImage('treelv6'); ?>

                    <h2 class="text-white content-heading "><?= htmlspecialchars($contentHeading) ?></h2>
                </div>
                <!-- <div class="loader-spinner"></div> -->
                <canvas id="myLottie" class="my-3" width="53" height="53" style="display: flex; justify-content: center; align-items: center; margin: 0 auto;"></canvas>
                <div id="loadingText" class="text-white mt-5"><?= htmlspecialchars($loadingText) ?></div>
            </div>
        </div>