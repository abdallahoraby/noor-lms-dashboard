<!-- template-parts/template-practice-module.php -->
<div class="practice-tracker">
    <div class="heading-with-icon d-flex gap-3 align-items-center">
        <img src="../assets/images/book-icon.svg" alt="">
        <h3> Practice tracker </h3>
    </div>

    <div class="practice-wrapper">
        <div class="practice-header">
            <h4> This Week Progress </h4>
            <div class="week-progress d-flex align-items-center justify-content-center mt-3 mb-3">
                <div class="day-progress">
                    <h4> Sat </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> Sun </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> Mon </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> Tue </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> Wed </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> Thu </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> Fri </h4>
                    <div class="has-practice"></div>
                </div>

            </div>
        </div>

        <div class="divider"></div>

        <div class="practice-log">

            <div class="d-flex justify-content-between gap-3 header">
                <h4> Your Practice Progress </h4>
                <span class="practice-score"> 65/100 </span>
            </div>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 65%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <p> Only 1 session left until you can unlock your next Course. Keep up the good work! </p>

            <a href="#" class="btn log-practice-btn open-practice-modal"> Log Your Practice </a>

            <a href="#" class="view-practice-logs"> View Practice Logs </a>

        </div>


    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="practice_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Log Your Practice </h5>
            </div>
            <div class="modal-body">
                <form id="practice-form">
                    <label for="practice_date"> Date of Practice? </label>
                    <input type="date" id="practice_date" name="practice_date">
                    <label for="practice_minutes"> How many minutes do you want to practice? </label>
                    <div class="radio-group">
                        <div class="radio-button">
                            <input type="radio" id="option-5" name="practice_minutes" value="5">
                            <label for="option-5">5</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-10" name="practice_minutes" value="10">
                            <label for="option-10">10</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-15" name="practice_minutes" value="15">
                            <label for="option-15">15</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-20" name="practice_minutes" value="20">
                            <label for="option-20">20</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-25" name="practice_minutes" value="25">
                            <label for="option-25">25</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-30" name="practice_minutes" value="30">
                            <label for="option-30">30</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-45" name="practice_minutes" value="45">
                            <label for="option-45">45</label>
                        </div>
                        <div class="radio-button">
                            <input type="radio" id="option-60" name="practice_minutes" value="60">
                            <label for="option-60">60</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal-btn" data-dismiss="modal">Close</button>
                <button type="button" id="save-practice">Save Practice</button>
            </div>
        </div>
    </div>
</div>


<?php

    $user_id = get_current_user_id();
    $item_id = 123; // Replace with the actual item ID
    $component_name = 'practice_tracker';
    $component_action = 'logged_practice';
    $notification_content = 'User logged a practice session';

    push_buddyboss_notification($user_id, $item_id, $component_name, $component_action, $notification_content);

?>

