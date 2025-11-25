<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Green Flyers API Backend</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #e8f5e9;
            color: #2e7d32;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(44, 160, 28, 0.08);
            padding: 3rem 2.5rem;
            text-align: center;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: .7rem;
            letter-spacing: -.5px;
            color: #1b5e20;
        }
        .desc {
            font-size: 1.15rem;
            margin-bottom: 1.7rem;
            color: #388e3c;
        }
        .endpoints {
            text-align: left;
            margin: 1.4rem auto 1rem auto;
            background: #f1f8e9;
            border-radius: 6px;
            display: inline-block;
            padding: 1.1rem 1.5rem;
            color: #33691e;
            font-size: 1rem;
        }
        .footer {
            color: #66bb6a;
            margin-top: 2.5rem;
            font-size: .95rem;
        }
        code {
            background: #dcedc8;
            border-radius: 4px;
            padding: .15em .35em;
            font-size: .95em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌱 Green Flyers API Backend</h1>
        <div class="desc">
            Welcome to the Green Flyers Laravel API backend.<br>
            This API manages flight itinerary data for eco-conscious air travellers.
        </div>
        
        <div class="endpoints">
            <b>Main API Endpoints:</b>
            <ul>
                <li><code>POST /api/itinerary</code> &mdash; create a new itinerary</li>
                <li><code>GET /api/itinerary</code> &mdash; list all itineraries</li>
                <li><code>GET /api/itinerary/{id}</code> &mdash; view a specific itinerary</li>
                <li><code>PUT /api/itinerary/{id}</code> &mdash; update an itinerary</li>
                <li><code>DELETE /api/itinerary/{id}</code> &mdash; delete an itinerary</li>
            </ul>
            <small>All endpoints require authentication.</small>
        </div>
        
        <div class="footer">
            Powered by Laravel • Green Flyers 2024
        </div>
    </div>
</body>
</html>
