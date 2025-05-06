<div class="tab-pane fade show active w-100 w-md-75 w-lg-50 mx-auto" id="profile" role="tabpanel" aria-labelledby="profile-tab">
    <h2 class="text-center text-white font-secondaryBold">Profile</h2>
    <div class="mt-3">
        <div class="text-center">
            <img id="avatarPreview" src="<?= $_SESSION['avatar_image'] ?? $images['default-user-image'] ?>" alt="Avatar"
                class="rounded-circle mb-2" width="100" height="100">
            <input type="file" id="avatarImage" accept="image/*">
        </div>
    </div>
    <form id="profileForm" class="text-white" enctype="multipart/form-data" method="POST">

        <div class="mb-3">
            <label for="profileUserName" class="form-label">Username<?php ?></label>
            <input type="text" class="form-control" id="profileUserName" placeholder="Zeeshanali96"
                value="<?php echo $_SESSION['username'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileName" class="form-label">Name<?php ?></label>
            <input type="text" class="form-control" id="profileName" placeholder="Enter full name"
                value="<?php echo $_SESSION['name'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileEmail" class="form-label">Email<?php ?></label>
            <input type="email" class="form-control text-lowercase text-white" id="profileEmail"
                placeholder="user@example.com" value="<?php echo $_SESSION['email'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileBio" class="form-label">Bio</label>
            <textarea class="form-control" id="profileBio" rows="3" placeholder="Tell us about yourself..."
                value="<?php echo $_SESSION['bio'] ?? ''; ?>"><?php echo $_SESSION['bio'] ?? ''; ?></textarea>
        </div>
        <div class="mt-2 d-flex justify-content-between items-center">
        <?php if (!isset($_SESSION['access_token']) || empty($_SESSION['access_token']['access_token'])): ?>
            <button class="btn btn-main-primary" onclick="window.location.href='<?php echo $_ENV['BASE_URL'] ?>/calendar/connect-calendar.php'" type="button" id="loginWithGoogle">
                <?= getGoogleIcon() ?>Link your Google Account
            </button>
            <?php endif; ?>
            <button class="btn btn-main-primary px-4" id="updateprofileSubmitbtn" type="submit">Update</button>
        </div>
    </form>
    <script>
        const updateprofileSubmitbtn = document.getElementById('updateprofileSubmitbtn');
        // Clicking avatar triggers file input
        document.getElementById('avatarPreview').addEventListener('click', function () {
            document.getElementById('avatarImage').click();
        });

        document.getElementById('avatarImage').addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);
            updateprofileSubmitbtn.disabled = true;
            updateprofileSubmitbtn.innerText = 'Updating Profile Image...';

            try {
                const res = await fetch('?api=upload_profile_image', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    document.getElementById('avatar_image_nav').src = data.image_url;
                    document.getElementById('avatarPreview').src = data.image_url;
                    iziToast.destroy();
                    iziToast.success({
                        title: 'Success',
                        message: 'Profile image updated!'
                    });
                } else {
                    iziToast.destroy();
                    iziToast.error({
                        title: 'Error',
                        message: data.message || 'Upload failed'
                    });
                }
                updateprofileSubmitbtn.disabled = false;
                updateprofileSubmitbtn.innerText = 'Update';
            } catch (err) {
                iziToast.destroy();
                iziToast.error({
                    title: 'Error',
                    message: 'Network error or server is offline.'
                });
              
                updateprofileSubmitbtn.disabled = false;
                updateprofileSubmitbtn.innerText = 'Update';
            }
        });

        document.getElementById('updateprofileSubmitbtn').addEventListener('click', function (e) {
    e.preventDefault(); // prevent normal form submission
    // updateprofileSubmitbtn.disabled = true;
    const username = document.getElementById('profileUserName').value.trim();
    const name = document.getElementById('profileName').value.trim();
    const email = document.getElementById('profileEmail').value.trim();
    const bio = document.getElementById('profileBio').value.trim();

    if (!username || !email) {
        iziToast.destroy();
        iziToast.error({
            title: 'Error',
            message: 'Username and Email are required.'
        });
        return;
    }

    fetch('?api=update_profile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, name, email, bio })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            iziToast.destroy();
            iziToast.success({
                title: 'Success',
                message: 'Profile updated successfully!'
            });

        } else {
            iziToast.destroy();
            iziToast.error({
                title: 'Error',
                message: 'Error: ' + data.message
            });
        }
    })
    .catch(error => {
        iziToast.destroy();
        iziToast.error({
            title: 'Error',
            message: 'Unexpected error occurred.'
        });
    });
    
});

    </script>
</div>