<script setup>
import logoPatudo from '@/../../assets/logoPatudo.svg';
</script>

<template>
    <div class="min-vh-100 d-flex flex-column bg-light bg-opacity-25 position-relative overflow-hidden">

        <div class="patudo-glow patudo-glow-top"></div>
        <div class="patudo-glow patudo-glow-bottom"></div>

        <div class="patudo-content d-flex flex-column flex-grow-1">

            <header class="container py-4">
                <nav class="d-flex align-items-center justify-content-between">

                    <a href="/"
                       class="navbar-brand fw-bolder fs-2 text-decoration-none text-body d-flex align-items-center gap-2">
                        <img :src="logoPatudo" alt="Logo Patudo" width="70">
                        Patudo
                    </a>

                    <div class="d-flex gap-2 col-6 col-md-4 col-lg-3">

                        <a href="/login"
                           class="btn btn-primary rounded-pill w-50 py-2 fw-bold">
                            Login
                        </a>

                        <a href="/register"
                           class="btn btn-outline-primary rounded-pill w-50 py-2 fw-bold">
                            Registar
                        </a>

                    </div>

                </nav>
            </header>

            <main class="container flex-grow-1 d-flex align-items-center py-5">

                <div class="row align-items-center g-5 w-100">

                    <div class="col-lg-6">

                        <h1 class="display-4 fw-bolder lh-sm mb-3">
                            O teu melhor amigo<br>
                            em <span class="text-primary">boas patas.</span>
                        </h1>

                        <p class="fs-4 text-secondary mb-4">
                            Encontra o cuidador ideal para o teu animal, perto de ti.
                        </p>

                        <a href="/find-petsitter"
                           class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-semibold shadow patudo-cta">
                            Procurar cuidador
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>

                    </div>

                    <div class="col-lg-6">

                        <img
                            src="https://images.unsplash.com/photo-1649032609232-11c3ce805807?q=80&w=812&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D"
                            class="img-fluid rounded-5 w-100 shadow-lg border border-4 border-white"
                            alt="Cão labrador feliz">

                    </div>

                </div>

            </main>

        </div>
    </div>
</template>
