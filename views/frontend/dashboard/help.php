<!-- 13. HELP & SUPPORT -->
<div id="tab-help" class="dash-panel <?php echo $activeTab === 'help' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Help & Support</h2>
            <p>Have questions? Submit a ticket to our concierge support team.</p>
        </div>
    </div>
    <form onsubmit="handleSupportSubmit(event)" style="max-width:500px;">
        <div class="form-grp">
            <label class="form-lbl">Subject</label>
            <input type="text" name="subject" class="form-inp" required placeholder="Question about order, candle care, etc.">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Message</label>
            <textarea name="message" class="form-inp" rows="4" required placeholder="Describe your request..."></textarea>
        </div>
        <button type="submit" class="btn-lvb">Submit Support Ticket</button>
    </form>
</div>
