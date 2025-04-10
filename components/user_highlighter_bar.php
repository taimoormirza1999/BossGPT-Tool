<?php
 // Display welcome message if set
 if (isset($_SESSION['welcome_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Toast('success', 'Welcome', '" . htmlspecialchars($_SESSION['welcome_message']) . "');
        });
    </script>";
    unset($_SESSION['welcome_message']);
}
?>
<div class="nav-container row justify-content-between nav-background">
    <div class="col-md-6 d-flex justify-content-between align-items-center self-center">
        <h4 class="text-capitalize font-weight-normal d-flex align-items-center" style="font-size: 1.83rem;">
            <span style="color: var(--bs-primary-white55percent);">Welcome, </span> <span class=" text-capitalize"
                style="color: var(--bs-primary-white); font-size: 1.43rem;">
                &nbsp;<?php echo $_SESSION['username']; ?>&nbsp;</span>&nbsp;👋
        </h4>
    </div>
    <ul class="col-md-6 nav nav-tabs mb-0 d-flex justify-content-end align-items-center" id="projectTabs"
        style="width: auto; ">
        <li class="nav-item">
            <button type="button" class="btn btn-sm btn-main-primary" data-bs-toggle="modal"
                data-bs-target="#assignUserModal">
                <?php echo getAddUserIcon();?> Invite User
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="btn btn-sm btn-main-primary" data-bs-toggle="modal"
                data-bs-target="#activityLogModal">
                <?php echo getClockIcon();?> Recent Actions
            </button>
        </li>
        <?php if (TESTING_FEATURE == 1): ?>
            <li class="nav-item">

                <button type="button" class="btn btn-sm btn-info" onclick='sendWelcomeEmailTest()'>
                    <i class="bi bi-clock-history"></i> Testing Feature
                </button>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <button type="button" class="btn btn-main-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                <?php echo getAddSquareIcon();?> New Project
            </button>
        </li>
    </ul>
</div>