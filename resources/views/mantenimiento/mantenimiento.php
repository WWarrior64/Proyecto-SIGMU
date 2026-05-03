<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimientos</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #eef1f5;
}

/* 🔴 BARRA SUPERIOR */
.header {
    background: #7b1e1e;
    color: white;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.menu-btn {
    font-size: 20px;
    cursor: pointer;
}

.logo {
    height: 30px;
}

.header-title {
    font-weight: bold;
}

.header-right {
    display: flex;
    gap: 10px;
}

.icon-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

/* CONTENIDO */
.container {
    padding: 20px;
}

/* LAYOUT */
.layout {
    display: flex;
    gap: 20px;
}

/* TARJETAS */
.card {
    background: white;
    border-radius: 12px;
    padding: 15px;
}

/* IZQUIERDA */
.calendar-section {
    flex: 1.2;
}

.calendar-title {
    background: #7b1e1e;
    color: white;
    padding: 10px;
    border-radius: 8px;
    font-weight: bold;
    margin-bottom: 10px;
}

/* CALENDARIO */
.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}

.day-name {
    font-size: 12px;
    text-align: center;
    color: #777;
    font-weight: bold;
}

.day {
    height: 70px;
    background: #f4f6f9;
    border-radius: 6px;
    padding: 5px;
    position: relative;
}

.day span {
    position: absolute;
    top: 5px;
    left: 6px;
    font-size: 12px;
}

/* EVENTO */
.event {
    background: #7b1e1e;
    color: white;
    font-size: 10px;
    padding: 2px 5px;
    border-radius: 4px;
    margin-top: 18px;
    display: inline-block;
}

/* DERECHA */
.list-section {
    flex: 1;
}

/* HEADER DERECHA */
.list-header {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    margin-bottom: 10px;
    border-radius: 10px;
    overflow: hidden;
}

/* TÍTULO (IZQUIERDA) */
.list-title {
    background: #7b1e1e;
    color: white;
    padding: 12px;
    font-weight: bold;
    flex: 1;
    display: flex;
    align-items: center;
    border-radius: 9px;
}

/* MINI PANEL DERECHO */
.list-stats {
    background: #e7e7e7; 
    color: rgb(0, 0, 0);
    padding: 10px 15px;
    font-size: 12px;
    text-align: right;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 140px;

    border: 1.5px solid #000000;
    border-radius: 9px; /* 👈 clave */
}

.stats {
    font-size: 12px;
    text-align: right;
    color: #ffffff;
}

/* ITEMS */
.item {
    display: flex;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    background: #f9fafc;
    margin-bottom: 10px;
    align-items: center;
}

.item img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
}

.item-info {
    flex: 1;
}

.item-info strong {
    display: block;
}

.item-info span {
    font-size: 12px;
    color: #666;
}

/* BOTON */
.btn {
    background: #7b1e1e;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
}

.btn:hover {
    background: #5a1414;
}
</style>
</head>

<body>

<!-- 🔴 BARRA SUPERIOR -->
<div class="header">
    <div class="header-left">
        <div class="menu-btn">☰</div>
        <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
        <div class="header-title">MANTENIMIENTO</div>
    </div>

    <div class="header-right">
        <button class="icon-btn">🔑</button>
        <button class="icon-btn">⎋</button>
    </div>
</div>

<!-- CONTENIDO -->
<div class="container">
    <div class="layout">

        <!-- IZQUIERDA -->
        <div class="calendar-section card">
            <div class="calendar-title">CALENDARIO DE REPARACIONES - MES ACTUAL</div>

            <div class="calendar">
                <div class="day-name">Sun</div>
                <div class="day-name">Mon</div>
                <div class="day-name">Tue</div>
                <div class="day-name">Wed</div>
                <div class="day-name">Thu</div>
                <div class="day-name">Fri</div>
                <div class="day-name">Sat</div>

                <!-- DÍAS -->
                <!-- (placeholder) -->
                <div class="day"><span>1</span></div>
                <div class="day"><span>2</span></div>
                <div class="day"><span>3</span><div class="event">A21</div></div>
                <div class="day"><span>4</span></div>
                <div class="day"><span>5</span></div>
                <div class="day"><span>6</span></div>
                <div class="day"><span>7</span></div>

                <div class="day"><span>8</span></div>
                <div class="day"><span>9</span></div>
                <div class="day"><span>10</span></div>
                <div class="day"><span>11</span></div>
                <div class="day"><span>12</span></div>
                <div class="day"><span>13</span></div>
                <div class="day"><span>14</span></div>

                <div class="day"><span>15</span></div>
                <div class="day"><span>16</span></div>
                <div class="day"><span>17</span></div>
                <div class="day"><span>18</span></div>
                <div class="day"><span>19</span></div>
                <div class="day"><span>20</span></div>
                <div class="day"><span>21</span></div>

                <div class="day"><span>22</span></div>
                <div class="day"><span>23</span></div>
                <div class="day"><span>24</span></div>
                <div class="day"><span>25</span></div>
                <div class="day"><span>26</span></div>
                <div class="day"><span>27</span></div>
                <div class="day"><span>28</span></div>
            </div>
        </div>

        <!-- DERECHA -->
        <div class="list-section card">
            <div class="list-header">
    
    <!-- IZQUIERDA -->
    <div class="list-title">
        ACTIVOS PENDIENTES
    </div>

    <!-- DERECHA (mini panel) -->
    <div class="list-stats">
        <div><strong>Reparaciones:</strong> 5</div>
        <div><strong>Técnicos:</strong> 2</div>
    </div>

</div>

            <div class="item">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png">
                <div class="item-info">
                    <strong>A21</strong>
                    <span>Edificio A</span>
                    <div>Problema en cañon</div>
                </div>
                <button class="btn">Programar</button>
            </div>
            
            <div class="item">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png">
                <div class="item-info">
                    <strong>B30</strong>
                    <span>Edificio B</span>
                    <div>Cable HDMI tiene falso</div>
                </div>
                <button class="btn">Programar</button>
            </div>

            <div class="item">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png">
                <div class="item-info">
                    <strong>C41</strong>
                    <span>Edificio C</span>
                    <div>Pupitre dañado</div>
                </div>
                <button class="btn">Programar</button>
            </div>

        </div>

    </div>
</div>

</body>
</html>