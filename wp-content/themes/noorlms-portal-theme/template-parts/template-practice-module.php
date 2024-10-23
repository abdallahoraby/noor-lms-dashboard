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
                    <h4> S </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> S </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> M </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> T </h4>
                    <div class="has-practice active"></div>
                </div>

                <div class="day-progress">
                    <h4> W </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> T </h4>
                    <div class="has-practice"></div>
                </div>

                <div class="day-progress">
                    <h4> F </h4>
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

            <a href="#" class="btn log-practice-btn"> Log Your Practice </a>

            <a href="#" class="view-practice-logs"> View Practice Logs </a>

        </div>


    </div>
</div>



<form id="practice-form">
    <input type="datetime-local" id="practice_datetime" name="practice_datetime" required>
    <input type="number" id="number_of_practices" name="number_of_practices" required>
    <button type="button" id="save-practice">Save Practice</button>
</form>