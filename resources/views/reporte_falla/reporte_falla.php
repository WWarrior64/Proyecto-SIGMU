<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tickets</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f0f0;
}

/* 🔴 HEADER */
.header {
    background: #7b1e1e;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    color: white;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.menu-btn {
    font-size: 22px;
}

.logo {
    height: 30px;
}

.header-right button {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

/* CONTENIDO */
.container {
    padding: 30px;
}

/* CARD */
.card {
    background: #ffffff;
    border-radius: 10px;
    padding: 30px;
}

/* TITULO */
.title {
    font-size: 32px;
    color: #373d3b;
    margin-bottom: 10px;
}

.divider {
    height: 1px;
    background: #aaa;
    margin-bottom: 25px;
}

/* FORM GRID */
.form-row {
    display: flex;
    gap: 40px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

label {
    font-size: 20px;
    font-weight: bold;
    color: #373d3b;
}

/* INPUTS */
input, select, textarea {
    padding: 10px;
    border: 1px solid #555;
    background: #f6f6f6;
    font-size: 16px;
}

textarea {
    height: 150px;
    resize: none;
}

/* BOTONES */
.actions {
    display: flex;
    justify-content: flex-end;
    gap: 20px;
    margin-top: 20px;
}

.btn {
    padding: 12px 30px;
    border-radius: 10px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}

/* CANCELAR */
.btn-cancel {
    background: #9e9e9e;
    color: white;
}

/* CONFIRMAR */
.btn-confirm {
    background: #e0b037;
    color: black;
}

.back-btn {
    font-size: 28px;
    color: #8a8a8a;
    margin: 15px 0 0 20px;
    cursor: pointer;
    width: fit-content;
}

.back-btn:hover {
    color: #555;
}

select {
    color: #8f8f8f;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="menu-btn">☰</div>
        <img src="https://via.placeholder.com/80x30" class="logo">
    </div>

    <div class="header-right">
        <button>⎋</button>
    </div>
</div>

<div class="back-btn" onclick="history.back()">←</div>

<!-- CONTENIDO -->
<div class="container">
    <div class="card">

        <div class="title">TICKETS</div>
        <div class="divider"></div>

        <!-- FILA 1 -->
        <div class="form-row">
            <div class="form-group">
                <label>Nombres:</label>
                <input type="text">
            </div>

            <div class="form-group">
                <label>Estado:</label>
                <select>
                    <option>Ejemplo</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de incidencia:</label>
                <select>
                    <option>Ejemplo</option>
                </select>
            </div>
        </div>

        <!-- DESCRIPCION -->
        <div class="form-group">
            <label>Descripción:</label>
            <textarea></textarea>
        </div>

        <!-- BOTONES -->
        <div class="actions">
            <button class="btn btn-cancel">CANCELAR</button>
            <button class="btn btn-confirm">CONFIRMAR</button>
        </div>

    </div>
</div>

</body>
</html>