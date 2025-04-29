<div class="tab-pane fade w-100 w-md-75 w-lg-50 mx-auto " id="settings" role="tabpanel" aria-labelledby="settings-tab">
    <div class="text-center mb-4">
        <h2 class="text-white font-secondaryBold">Settings</h2>
    </div>

    <!-- Password Section -->
    <div class="mb-4">
        <label for="password" class="form-label text-white">Password</label>
        <div class="input-group rounded-2 p-2">
            <input type="password" class="form-control text-white bg-transparent primary-input" id="password" placeholder="Password">
            <button class="btn btn-outline-action" type="button" id="togglePassword">
                <?php echo getEditIcon(); ?>
            </button>
        </div>
    </div>
    <script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const newPassword = passwordInput.value.trim();

    if (!newPassword) {
        iziToast.error({
            title: 'Error',
            message: 'Please enter a password!',
            position: 'topRight'
        });
        return;
    }

    fetch('?api=update_password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ password: newPassword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
          iziToast.success({
            title: 'Success',
            message: 'Password updated successfully!',
            position: 'topRight'
          });
            passwordInput.value = ''; // clear the input
        } else {
            iziToast.error({
                title: 'Error',
                message: 'Failed to update password: ' + data.message,
                position: 'topRight'
            });
        }
    })
    .catch(error => {
        console.error('Error updating password:', error);
        iziToast.error({
            title: 'Error',
            message: 'An unexpected error occurred.',
            position: 'topRight'
        });
    });
});
</script>
    

    <!-- Themes Section -->
    <div class="mb-5">
        <label class="form-label text-white">Themes</label>
        <div class="d-flex justify-content-center gap-4 flex-wrap">
            <!-- Theme Items -->
            <?php
$colorThemes = [
    ['color' => 'linear-gradient(152.14deg, #240121 8.15%, #8A047F 91.85%);
', 'name' => 'Purple', 'theme' => 'purple-mode'],
    ['color' => 'linear-gradient(156.22deg, #0B020F 8.55%, #561075 91.45%);
', 'name' => 'Dark Purple', 'theme' => 'dark-purple-mode'],
    ['color' => 'linear-gradient(153.27deg, #410404 8.31%, #A70A0A 91.69%);
', 'name' => 'Brown', 'theme' => 'brown-mode'],
    ['color' => 'linear-gradient(156.22deg, #000000 8.55%, #FFFFFF 91.45%);
', 'name' => 'Dark', 'theme' => 'dark-mode'],
];
?>

<?php foreach ($colorThemes as $theme): ?>
    <div class="text-center d-flex justify-content-center align-items-center flex-column">
        <div 
            class="rounded-circle "
            style="cursor: pointer; width: 50px; height: 50px; background: <?php echo htmlspecialchars($theme['color']); ?>;"
            onclick="changeTheme('<?php echo $theme['theme']; ?>')"
        >
            <i class="fas fa-check"></i>
        </div>
        <small class="text-white d-block mt-2"><?php echo htmlspecialchars($theme['name']); ?></small>
    </div>
<?php endforeach; ?>

        </div>
    </div>
</div>