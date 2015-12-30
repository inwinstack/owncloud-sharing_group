<div id="controls">
    <div class="sg-toolbar">
        <div class="sg-dropdown">
            <div type="button" class="sg-dropdown-toggle" id="toggle-checkbox">
                <input type="checkbox" class="icon" id="checkuser">
                <span class="caret" id="caret-checkbox"></span>
            </div>

            <ul class="sg-dropdown-menu checkuser" hidden=true>
                <li><a href="#" id="check-all">All</a></li>
                <li><a href="#" id="clear-all">None</a></li>
                <li><a href="#" id="inverse">Inverse</a></li>
            </ul>
        </div>

        <div class="sg-dropdown" id="sg-dropdown-group">
            <div type="button" class="sg-dropdown-toggle" id="toggle-group">
                <span>Groups</span>
                <span class="caret" id="caret-group"></span>
            </div>

            <div class="sg-dropdown-menu group" hidden=true>
                <div class="sg-dropdown-body">
                    <ul class="sg-dropdown-scrollable">

                    </ul>
                </div>
                <div class="sg-dropdown-footer">
                    <div class="btn-group btn-group-justified">
                        <div type="button" class="btn-flat" id="cancel">CANCEL</div>
                        <div type="button" class="btn-flat" id="multi-group-select">APPLY</div>
                    </div>
                </div>
            </div>
        </div> 
    </div>
</div>
