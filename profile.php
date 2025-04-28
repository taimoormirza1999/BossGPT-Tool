<?php
function include_profile($images)
{
    require_once 'components/misc.php';
    ?>
    <?php require_once 'components/modals.php'; ?>
    <style>
        .calendar-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #dateRangeButton {
            background-color: transparent;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.3rem 0.5rem;
        }
    </style>
    <style>
        .tabs-pannel .nav-tabs .nav-link.active {

            color: #fff !important;
        }

        .tabs-pannel .nav-tabs .nav-link {

            color: rgba(255, 255, 255, 0.5) !important;
        }

        #avatarImage {
            display: none;
        }

        #avatarPreview {
            cursor: pointer;
            border: 2px solid white;
            width: 6.75rem;
            height: 6.75rem;
            object-fit: cover;
            object-position: center;
        }

        div.tab-pane {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(234, 234, 234, 0.3);
            backdrop-filter: blur(6.35px);
            border-radius: 16px;
            margin: auto;
            padding: 2.5rem 1.4rem;
            overflow-y: hidden;
        }

        div#profile {
            width: 50%;
            max-width: 576px;
            margin-bottom: 2rem !important;
        }

        div#settings {
            width: 50%;
            max-width: 576px;
        }

        div#activity,
        div#cards {
            width: 95%;
            height: calc(100vh - 200px);
        }

        div#profile .form-control {
            background-color: transparent;
            border: 1px solid rgba(156, 156, 156, 1);
        }

        div#profile input.form-control {
            height: 48px;
            color: white !important;
        }

        div#profile input.form-control::placeholder,
        div#profile textarea.form-control::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
        }
    </style>
    <div class="container-fluid pb-3">
        <!-- Main Content Area -->
        <div class="row sides-padding " style="width: 99.4%!important;">
            <button class="btn btn-link p-0 text-white show open-icon-btn " data-bs-dismiss="modal" aria-label="Close"
                onclick="openChatPannel()"><?php echo getIconImage(0, 0, "2.5rem", "auto", "https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png", 0); ?></button>
            <!-- Tasks Panel (Board) - now spans 9 columns -->
            <div class="col-12 col-md-12 tasks-panel">
                <div class="card h-100 projects_card tabs-pannel">
                    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                        <ul class="py-0 px-0 my-0 nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-transparent border-0 font-secondaryLight" id="profile-tab"
                                    data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-transparent border-0 px-2 font-secondaryLight" id="activity-tab"
                                    data-bs-toggle="tab" data-bs-target="#activity" type="button"
                                    role="tab">Activity</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-transparent border-0 font-secondaryLight" id="cards-tab"
                                    data-bs-toggle="tab" data-bs-target="#cards" type="button" role="tab">Cards</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-transparent border-0 px-2 font-secondaryLight" id="settings-tab"
                                    data-bs-toggle="tab" data-bs-target="#settings" type="button"
                                    role="tab">Settings</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="content-container h-100"
                            style=" background: rgba(24, 25, 28, 0.5);backdrop-filter: blur(14.7px);border-radius: 0 0 16px 16px; overflow-y: scroll;">
                            <div class="card-body p-0">
                                <div class="tab-content p-3" id="profileTabsContent"
                                    style="height: 80vh!important; background: rgba(24, 25, 28, 0.5); backdrop-filter: blur(14.7px); border-radius: 16px;overflow: scroll;">

                                    <?php require_once 'components/profile/tabProfile.php'; ?>
                                    <?php require_once 'components/profile/tabActivity.php'; ?>
                                    <?php require_once 'components/profile/tabCards.php'; ?>
                                    <?php require_once 'components/profile/tabSettings.php'; ?>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateRangeButton = document.getElementById('dateRangeButton');
            const selectedDateRange = document.getElementById('selectedDateRange');

            const today = new Date();
            const fiveDaysAgo = new Date();
            fiveDaysAgo.setDate(today.getDate() - 5);

            const picker = flatpickr(document.createElement('input'), {
                mode: 'range',
                dateFormat: 'd M Y',
                defaultDate: [fiveDaysAgo, today],
                theme: 'dark',
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const startFormatted = formatDate(selectedDates[0]);
                        const endFormatted = formatDate(selectedDates[1]);

                        selectedDateRange.textContent = `${startFormatted} - ${endFormatted}`;

                        // Call loadActivityLog2 with selected dates in backend format (Y-m-d)
                        const startForBackend = formatDateForBackend(selectedDates[0]);
                        const endForBackend = formatDateForBackend(selectedDates[1]);
                        // loadActivityLog2(startForBackend, endForBackend);
                    }
                }
            });

            dateRangeButton.addEventListener('click', function () {
                picker.open();
            });

            // Helper functions
            function formatDate(date) {
                const options = { day: '2-digit', month: 'short' };
                return date.toLocaleDateString('en-GB', options);
            }

            function formatDateForBackend(date) {
                return date.toISOString().split('T')[0]; // YYYY-MM-DD
            }
            // Auto-load logs for default 5 days range
            loadActivityLog2(formatDateForBackend(fiveDaysAgo), formatDateForBackend(today));
        });

    </script>
<?php } ?>