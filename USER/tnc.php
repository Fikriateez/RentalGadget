<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | MemoryLens</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #0ea5e9;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-dim: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            line-height: 1.7;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-card);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--bg-body);
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .header h1 span { color: var(--accent); }

        .header p {
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        section {
            margin-bottom: 30px;
        }

        h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        p, li {
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 10px;
        }

        ul {
            padding-left: 20px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .btn-close {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }

        .btn-close:hover {
            opacity: 0.9;
        }

        @media (max-width: 600px) {
            .container { padding: 25px; }
            .header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Memory<span>Lens</span></h1>
            <p>Rental Terms & Conditions</p>
            <p>Last Updated: <?php echo date('d M Y'); ?></p>
        </div>

        <section>
            <h2>1. Rental Agreement</h2>
            <p>By booking a device with MemoryLens, you agree to comply with all the terms stated below. The "Renter" refers to the individual making the booking, and "MemoryLens" refers to the equipment owner.</p>
        </section>

        <section>
            <h2>2. Security Deposit</h2>
            <ul>
                <li>A security deposit is required for every device rental as specified during the booking process.</li>
                <li>The deposit status will remain <strong>"Unpaid"</strong> until confirmed by the Admin at our physical counter.</li>
                <li>Refunds of the security deposit are processed only after the device is returned and inspected for damages.</li>
            </ul>
        </section>

        <section>
            <h2>3. Damage & Loss Policy</h2>
            <ul>
                <li>The Renter is responsible for the safety and condition of the device during the rental period.</li>
                <li><strong>Damage Deduction:</strong> If the device is returned with damages (scratches, cracks, internal errors), MemoryLens reserves the right to deduct a specific amount from the security deposit.</li>
                <li>In the event of total loss or theft, the Renter is liable to pay the full current market value of the equipment.</li>
            </ul>
        </section>

        <section>
            <h2>4. Rental Duration & Late Fees</h2>
            <ul>
                <li>The rental period starts from the "Pickup Time" and ends at the "Return Time" selected during booking.</li>
                <li>Late returns will incur additional charges based on the current hourly or daily rate of the device.</li>
                <li>Please notify us at least 2 hours in advance if you wish to extend your rental period.</li>
            </ul>
        </section>

        <section>
            <h2>5. Identification Requirements</h2>
            <p>During pickup, the Renter must provide a valid Physical IC (MyKad) or Passport for verification purposes. MemoryLens reserves the right to cancel the booking if identification is not provided.</p>
        </section>

        <section>
            <h2>6. Cancellation</h2>
            <p>Cancellations must be made at least 24 hours before the scheduled pickup time. Refunds for rental fees (if any) are subject to our refund policy.</p>
        </section>

        <div class="footer">
            <a href="javascript:window.close();" class="btn-close">Close This Window</a>
        </div>
    </div>

</body>
</html>