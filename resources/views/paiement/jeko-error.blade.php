<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement échoué</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1D603D 0%, #0B482F 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #fee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #c33;
        }

        h1 {
            color: #c33;
            font-size: 28px;
            margin-bottom: 15px;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn-primary {
            background: #1D603D;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #0B482F;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: #666;
            border: 2px solid #e0e0e0;
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 14px;
            color: #999;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">✕</div>
        <h1>Paiement échoué</h1>
        <p>
            Votre paiement a echoué. 
            Veuillez réessayer ou contacter notre service client si le problème persiste.
        </p>

        <div class="info-box">
            <div class="info-item">
                <span class="info-label">Référence</span>
                <span class="info-value">{{ request()->get('reference') ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date</span>
                <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn-primary">Réessayer le paiement</a>
        <a href="/" class="btn-secondary">Retour à l'accueil</a>
    </div>
</body>
</html>
