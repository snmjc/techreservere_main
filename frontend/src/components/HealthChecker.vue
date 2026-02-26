<template>
  <div class="bg-white shadow mx-auto p-6 rounded w-full max-w-md">
    <h2 class="mb-4 font-semibold text-xl">Health Checker</h2>

    <div class="flex gap-3 mb-4">
      <button
        @click="checkBackend"
        :disabled="backend.status === 'loading'"
        class="bg-blue-600 px-4 py-2 rounded text-white"
      >
        Check Backend
      </button>
      <button
        @click="checkDB"
        :disabled="db.status === 'loading'"
        class="bg-green-600 px-4 py-2 rounded text-white"
      >
        Check DB
      </button>
    </div>

    <div class="space-y-2">
      <div class="flex justify-between items-center">
        <div class="font-medium">Backend</div>
        <div :class="statusClass(backend.status)">{{ backend.message }}</div>
      </div>

      <div class="flex justify-between items-center">
        <div class="font-medium">Database</div>
        <div :class="statusClass(db.status)">{{ db.message }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue";

const backend = reactive({ status: "idle", message: "Not checked" });
const db = reactive({ status: "idle", message: "Not checked" });

const apiBase = import.meta.env.VITE_API_BASE_URL || "";

const statusClass = (s) => {
  if (s === "ok") return "text-green-600 font-semibold";
  if (s === "loading") return "text-yellow-600";
  if (s === "error") return "text-red-600 font-semibold";
  return "text-gray-600";
};

async function checkBackend() {
  backend.status = "loading";
  backend.message = "Checking...";
  try {
    const res = await fetch(`${apiBase}/health`, { method: "GET" });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    backend.status = "ok";
    backend.message = data.status || "OK";
  } catch (e) {
    backend.status = "error";
    backend.message = e.message;
  }
}

async function checkDB() {
  db.status = "loading";
  db.message = "Checking...";
  try {
    const res = await fetch(`${apiBase}/health/db`, { method: "GET" });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status} ${text}`);
    }
    const data = await res.json();
    db.status = data.db === "ok" ? "ok" : "error";
    db.message = data.db === "ok" ? "OK" : data.message || JSON.stringify(data);
  } catch (e) {
    db.status = "error";
    db.message = e.message;
  }
}
</script>

<style scoped>
/* minimal scoped styles - Tailwind is used for layout */
</style>
