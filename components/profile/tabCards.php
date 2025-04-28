<div class="tab-pane fade" id="cards" role="tabpanel"
                                                aria-labelledby="cards-tab">
                                                <!-- Top Bar with Project Dropdown + Date Filter -->
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-4 card-header p-0">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            id="projectDropdownButton3">
                                                            Select Project
                                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M4 6L8 10L12 6" stroke="currentColor"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                        <ul class="dropdown-menu" id="projectDropdown3">
                                                            <!-- Dynamically loaded items will be appended here -->
                                                        </ul>
                                                    </div>

                                                    <!-- Date Range Filter -->
                                                    <div class="d-flex align-items-center gap-2">
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
                                                <table class="table text-white table-hover align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Assign to</th>
                                                            <th>Due Date</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tasksTableBody">
                                                        <!-- Tasks will be inserted dynamically here -->
                                                    </tbody>
                                                </table>
                                            </div>