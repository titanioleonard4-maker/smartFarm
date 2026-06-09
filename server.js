require("dotenv").config();

const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const mongoose = require("mongoose");
const { Pool } = require("pg");

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

const dbMySQL = new Pool({
  host: process.env.PG_HOST,
  port: process.env.PG_PORT,
  database: process.env.PG_DATABASE,
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  ssl: { rejectUnauthorized: false },
});

// Test koneksi Supabase saat startup
dbMySQL
  .connect()
  .then((client) => {
    console.log("Supabase PostgreSQL Terhubung");
    client.release();
  })
  .catch((err) => {
    console.error("Supabase gagal terhubung:", err);
    process.exit(1);
  });

const mongotio = async () => {
  await mongoose
    .connect(process.env.MONGO_URI)
    .then(() => console.log("MongoDB Terhubung"))
    .catch((err) => console.log(err));
};

mongotio();

const suhuSchema = new mongoose.Schema({
  id_hewan: String,
  suhu: Number,
  detak_jantung: Number,
  pos_x: Number,
  pos_y: Number,
  timestamp: { type: Date, default: Date.now },
});
const SuhuLog = mongoose.model("SuhuLog", suhuSchema);

io.on("connection", (socket) => {
  socket.on("join_room", ({ id_peternak, is_admin }) => {
    if (is_admin) {
      socket.join("admin");
      console.log(`[Socket] Admin terhubung (socket: ${socket.id})`);
    } else {
      socket.join("peternak_" + id_peternak);
      console.log(
        `[Socket] Peternak ${id_peternak} terhubung (socket: ${socket.id})`,
      );
    }
  });
});

const PELUANG_ABNORMAL = 0.15;

function generateSensor(abnormal) {
  if (abnormal) {
    const tipe = Math.floor(Math.random() * 3);
    const suhu =
      tipe === 0 || tipe === 2
        ? parseFloat((Math.random() * (41.5 - 39.1) + 39.1).toFixed(1))
        : parseFloat((Math.random() * (39 - 36) + 36).toFixed(1));

    const detak =
      tipe === 1 || tipe === 2
        ? Math.random() < 0.5
          ? Math.floor(Math.random() * (54 - 40) + 40)
          : Math.floor(Math.random() * (120 - 101) + 101)
        : Math.floor(Math.random() * (100 - 55) + 55);

    return { suhu, detak_jantung: detak };
  } else {
    return {
      suhu: parseFloat((Math.random() * (39 - 36.5) + 36.5).toFixed(1)),
      detak_jantung: Math.floor(Math.random() * (100 - 55) + 55),
    };
  }
}

setInterval(async () => {
  try {
    const { rows } = await dbMySQL.query(`
      SELECT h.id_hewan, h.id_peternak, h.kode_kandang, k.posisi_x, k.posisi_y 
      FROM hewan h 
      JOIN kandang k ON h.kode_kandang = k.kode_kandang
    `);
    let abnormalCount = 0;

    for (const hewan of rows) {
      const isAbnormal = Math.random() < PELUANG_ABNORMAL;
      const sensor = generateSensor(isAbnormal);
      if (isAbnormal) abnormalCount++;

      const dataSensor = {
        id_hewan: hewan.id_hewan,
        id_peternak: hewan.id_peternak,
        kode_kandang: hewan.kode_kandang,
        pos_x: hewan.posisi_x,
        pos_y: hewan.posisi_y,
        suhu: sensor.suhu,
        detak_jantung: sensor.detak_jantung,
      };

      const log = new SuhuLog(dataSensor);
      await log.save();

      io.to("peternak_" + hewan.id_peternak).emit("update_suhu", dataSensor);
      io.to("admin").emit("update_suhu", dataSensor);
    }


  } catch (error) {
    console.error("Error:", error);
  }
}, 10000);

server.listen(process.env.PORT, () =>
  console.log(`Server Node.js aktif di port ${process.env.PORT}`),
);
