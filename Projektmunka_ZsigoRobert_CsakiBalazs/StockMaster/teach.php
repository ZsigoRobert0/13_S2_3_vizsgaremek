<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oktatóanyag</title>

    <style>
    body{ background:#07111f; color:#fff; font-family:system-ui; margin:0; }
    .wrap{ max-width:1100px; margin:30px auto; padding:0 14px; }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }

    .card{ margin-top:18px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:16px; backdrop-filter: blur(14px); }
    .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    .row{ display:flex; gap:10px; align-items:end; flex-wrap:wrap; }
    .field{ display:flex; flex-direction:column; gap:6px; position:relative; }

    .btn{
      background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      color:#fff;
      border-radius:12px;
      padding:10px 14px;
      cursor:pointer;
      font-weight:600;
    }
    .btn:hover{
      background:rgba(255,255,255,0.10);
      border-color:rgba(255,255,255,0.14);
    }

    input, select{
      -webkit-appearance:none; -moz-appearance:none; appearance:none;
      background: rgba(0,0,0,0.28);
      border: 1px solid rgba(255,255,255,0.10);
      color: #fff;
      padding: 10px 42px 10px 12px;
      border-radius: 12px;
      outline: none;
      min-width: 140px;
    }
    input:hover, select:hover{ border-color: rgba(255,255,255,0.16); }
    input:focus, select:focus{
      border-color: rgba(255,255,255,0.22);
      box-shadow: 0 0 0 3px rgba(255,255,255,0.06);
    }

    .selectField::after{
      content:"▾";
      position:absolute;
      right:14px;
      top:38px;
      color: rgba(255,255,255,0.65);
      pointer-events:none;
      font-size:14px;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button{
      -webkit-appearance:none; margin:0;
    }
    input[type="number"]{ -moz-appearance:textfield; }

    select option{ background:#0b1422; color:#fff; }

    .ok{ color:#2dd36f; }
    .bad{ color:#ff4d4d; }
    .muted{ opacity:0.7; }

    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.06); text-align:left; }
    th{ opacity:0.75; font-weight:700; }

    .tag{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; border:1px solid rgba(255,255,255,0.10); }
    .tag.deposit{ background:rgba(45,211,111,0.12); }
    .tag.withdrawal{ background:rgba(255,77,77,0.12); }
    .tag.trade{ background:rgba(64,155,255,0.12); }

    .backBtn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      color:#fff;
      text-decoration:none;
      font-weight:600;
      transition:.15s ease;
      white-space:nowrap;
    }
    .backBtn:hover{
    
      background: rgba(255,255,255,0.10);
      border-color: rgba(255,255,255,0.14);
    }
    .backBtn::before{ content:"←"; opacity:.75; }
    .fullBtn{
    
     display:block;
     width:90%;
     margin-top:12px;
     padding:12px 16px;
     border-radius:12px;
     background: rgba(255,255,255,0.06);
     border: 1px solid rgba(255,255,255,0.08);
     color:#fff;
     text-align:center;
     text-decoration:none;
     font-weight:700;
   }

   .fullBtn:hover{
     background: rgba(255,255,255,0.10);
     border-color: rgba(255,255,255,0.14);
    }
    .oktato{
         
        color:#fff;
      text-decoration:none;
      font-weight:1000;
    }

    @media (max-width: 850px){
      .grid{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

   <div class="wrap">
        
        <div class="card">
            <h1 class="oktato">Oktatóanyagok</h1>
            <div style="display:flex; justify-content:flex-end;">
                
               <a href="index.php" class="backBtn">Vissza a főoldalra</a>

            </div>

            <div style="max-height:520px; overflow:auto; margin-top:12px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.02);">
            <!-- teljes szélességű bevezető gomb -->
            <a href="start_teach.php" class="fullBtn">Bevezetés a tőzsde világába</a>
            
            <div style="max-height:520px; overflow:auto; margin-top:12px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.02);">
                <!-- Ide kerülhet később tartalom -->
            </div>
        </div>
 
     </div>
    
</body>
</html>