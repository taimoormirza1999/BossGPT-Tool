<div class="tab-pane fade" id="activity" role="tabpanel"
                                                aria-labelledby="activity-tab">
                                                <!-- Top Bar with Project Dropdown + Date Filter -->
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-4 card-header p-0">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            id="projectDropdownButton">
                                                            Select Project
                                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M4 6L8 10L12 6" stroke="currentColor"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                        <ul class="dropdown-menu hide-scrollbar" id="projectDropdown">
                                                            <!-- Dynamically loaded items will be appended here -->
                                                        </ul>
                                                    </div>

                                                    <!-- Date Range Filter -->
                                                    <div class="d-flex align-items-center gap-2 main_date_range_filter">
                                                        <!-- Calendar Icon -->
                                                        <div class="calendar-icon">
                                                            <?php echo getCalendarIcon(24, 24); ?>
                                                        </div>

                                                        <!-- Date Range Display -->
                                                        <button id="dateRangeButton"
                                                            class="btn btn-dark text-white d-flex align-items-center gap-2 border-0">
                                                            <span id="selectedDateRange">Select Date Range</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div id="activityLogList" class="hide-scrollbar list-group w-1/2 mx-auto"
                                                    style="max-height: 95%; overflow-y: auto; width: 55%;">
                                                    <!-- Repeat for more activities dynamically -->
                                                </div>
                                            </div>