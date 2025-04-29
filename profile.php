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

        .flatpickr-calendar {
            position: absolute;
            z-index: 9999;
            top: 100%;
            left: 0;
        }

        .flatpickr-calendar.open {
            top: 260px !important;
        }
    </style>
    <style>
        .tabs-pannel .nav-tabs .nav-link.active {
            color: #fff !important;
        }

        .tabs-pannel .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.5) !important;
        }

        span.flatpickr-day.today.selected.endRange,
        .flatpickr-day.selected.startRange,
        .flatpickr-day.startRange.startRange,
        .flatpickr-day.endRange.startRange,
        .flatpickr-day.selected.endRange,
        .flatpickr-day.startRange.endRange,
        .flatpickr-day.endRange.endRange {
            background: #000000 !important;
            border: solid 1px #000000 !important;
        }

        #avatarImage {
            display: none;
        }

        #avatarPreview {
            cursor: pointer;
            border: 2px dashed rgba(255, 255, 255, 0.5);
            width: 6.75rem;
            height: 6.75rem;
            object-fit: cover;
            object-position: center;
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
            padding: 1.5rem 1.4rem !important;
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
                        <style>
                            #profileTabs .nav-link {
                                padding: 0.5rem;
                            }
                        </style>
                        <?php
                        $tabs = [
                            ['id' => 'profile', 'label' => 'Profile'],
                            ['id' => 'activity', 'label' => 'Activity'],
                            ['id' => 'cards', 'label' => 'Cards'],
                            ['id' => 'settings', 'label' => 'Settings']
                        ];
                        ?>
                        <ul class="py-0 px-2 my-0 nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <?php foreach ($tabs as $index => $tab): ?>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link bg-transparent border-0 font-secondaryLight  <?php echo $index === 0 ? 'active' : ''; ?>"
                                        id="<?php echo $tab['id']; ?>-tab" data-bs-toggle="tab"
                                        data-bs-target="#<?php echo $tab['id']; ?>" type="button" role="tab"
                                        style="font-size: 1.2rem; font-weight: 700;">
                                        <?php echo htmlspecialchars($tab['label']); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="content-container h-100"
                            style=" background: rgba(24, 25, 28, 0.5);backdrop-filter: blur(14.7px);border-radius: 0 0 16px 16px; overflow-y: scroll;">
                            <div class="card-body p-0">
                                <div class="tab-content p-3 d-flex justify-content-center align-items-center"
                                    id="profileTabsContent"
                                    style="height: 80vh!important; background: rgba(24, 25, 28, 0.0); backdrop-filter: blur(14.7px); border-radius: 16px;overflow: scroll;">

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
        document.querySelectorAll('#profileTabs button[data-bs-toggle="tab"]').forEach(function (tabButton) {
            tabButton.addEventListener('shown.bs.tab', function (event) {
                const targetId = event.target.getAttribute('data-bs-target').substring(1); // get tab id without '#'

                if (targetId === 'cards') {
                    // alert('cards');
                    // return;
                    // Send to server to save in session
                    fetch('?api=set_selected_tab', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ selected_tab: targetId })
                    }).then(response => {
                        // Optionally check response if you want
                        console.log('Selected tab saved to session');
                    }).catch(error => {
                        console.error('Error saving tab selection:', error);
                    });
                }
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            const dateRangeButton = document.getElementById('dateRangeButton');
            const dateRangeButtonCardTab = document.getElementById('dateRangeButtonCardTab');
            const selectedDateRangeCardTab = document.getElementById('selectedDateRangeCardTab');
            const selectedDateRange = document.getElementById('selectedDateRange');

            const today = new Date();
            const fiveDaysAgo = new Date();
            fiveDaysAgo.setDate(today.getDate() - 5);
            const calendarWrapper = document.createElement('div');
            const calendarWrapperCardTab = document.createElement('div');
            calendarWrapper.classList.add('flatpickr-calendar-wrapper');
            document.querySelector('.main_date_range_filter').appendChild(calendarWrapper);
            calendarWrapperCardTab.classList.add('flatpickr-calendar-wrapper');
            document.querySelector('.card_tab_main_date_range_filter').appendChild(calendarWrapperCardTab);
            const picker = flatpickr(calendarWrapper, {
                mode: 'range',
                dateFormat: 'd M Y',
                defaultDate: [fiveDaysAgo, today],
                position: "below",
                theme: 'dark',
                // inline: 'true',
                // wrap: 'true',
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const startFormatted = formatDate(selectedDates[0]);
                        const endFormatted = formatDate(selectedDates[1]);

                        selectedDateRange.textContent = `${startFormatted} - ${endFormatted}`;

                        // Call loadActivityLog2 with selected dates in backend format (Y-m-d)
                        const startForBackend = formatDateForBackend(selectedDates[0]);
                        const endForBackend = formatDateForBackend(selectedDates[1]);
                        // loadActivityLog2(startForBackend, endForBackend);
                        // alert(`You have selected a date range: ${startForBackend} - ${endForBackend}`);
                        fetchNotifications(getLastSelectedProject(), startForBackend, endForBackend);
                    }
                }
            });
            const pickerCardTab = flatpickr(calendarWrapperCardTab, {
                mode: 'range',
                dateFormat: 'd M Y',
                defaultDate: [fiveDaysAgo, today],
                theme: 'dark',
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        const startFormatted = formatDate(selectedDates[0]);
                        const endFormatted = formatDate(selectedDates[1]);
                        selectedDateRangeCardTab.textContent = `${startFormatted} - ${endFormatted}`;
                        const startForBackend = formatDateForBackend(selectedDates[0]);
                        const endForBackend = formatDateForBackend(selectedDates[1]);
                        // loadActivityLog2(startForBackend, endForBackend);
                        // alert(`You have selected a date range: ${startFormatted} - ${endFormatted}`);
                        loadTasks2(getLastSelectedProject(), startForBackend, endForBackend);
                    }
                }
            });

            dateRangeButton.addEventListener('click', function () {
                picker.open();
            });

            dateRangeButtonCardTab.addEventListener('click', function () {
                pickerCardTab.open();
            });

      

        });

    </script>
<?php } ?>