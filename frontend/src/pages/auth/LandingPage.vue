<template>
  <div class="landing-page">
    <header class="landing-header">
      <div class="landing-shell landing-header-inner">
        <button class="landing-brand" type="button" @click="scrollToSection('home')">
          <img :src="logoPrimary" alt="TechReserve" class="landing-brand-logo" />
        </button>

        <nav class="landing-nav" aria-label="Primary navigation">
          <button
            v-for="item in navItems"
            :key="item.id"
            type="button"
            class="landing-nav-link"
            :class="{ 'is-active': activeSection === item.id }"
            @click="scrollToSection(item.id)"
          >
            {{ item.label }}
          </button>
        </nav>

        <button type="button" class="landing-signin-button" @click="goToSignIn">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"
              fill="none"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
            />
          </svg>
          <span>Sign in</span>
        </button>
      </div>
    </header>

    <main>
      <section id="home" class="landing-hero-section">
        <div class="landing-shell landing-hero">
          <div class="landing-hero-copy">
            <span class="landing-kicker">SMART. ORGANIZED. EFFICIENT.</span>
            <h1>Smarter Facility Management, Better Experiences</h1>
            <p>
              TechReserve streamlines the way equipment, venues, and reservations are
              managed, empowering teams to stay organized and ready.
            </p>

            <div class="landing-hero-actions">
              <button type="button" class="landing-primary-button" @click="goToSignIn">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M5 7h14a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1Z"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                  />
                </svg>
                <span>Make a Reservation</span>
              </button>

              <button type="button" class="landing-secondary-button" @click="scrollToSection('about')">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M12 5v14M5 12h14"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                  />
                </svg>
                <span>Learn More</span>
              </button>
            </div>
          </div>

          <div class="landing-hero-visual" aria-hidden="true">
            <div class="landing-hero-orb landing-hero-orb-left" />
            <div class="landing-hero-orb landing-hero-orb-right" />
            <div class="landing-hero-grid landing-hero-grid-top" />
            <div class="landing-hero-grid landing-hero-grid-bottom" />
            <div class="landing-hero-image-shell">
              <img :src="heroImage" alt="FEU Institute of Technology building" class="landing-hero-image" />
            </div>
          </div>
        </div>
      </section>

      <section class="landing-features-section">
        <div class="landing-shell landing-features-grid">
          <article v-for="feature in featureCards" :key="feature.title" class="landing-feature-card">
            <div class="landing-feature-icon" v-html="feature.icon" />
            <div>
              <h2>{{ feature.title }}</h2>
              <p>{{ feature.description }}</p>
            </div>
          </article>
        </div>
      </section>

      <section id="about" class="landing-about-section">
        <div class="landing-shell landing-about">
          <div class="landing-about-copy">
            <span class="landing-section-label">ABOUT TECHRESERVE</span>
            <h2>Built for Efficiency. Designed for People.</h2>
            <p>
              TechReserve is a web-based system designed to simplify the management of
              facilities, equipment, and reservations within educational institutions.
            </p>

            <div class="landing-persona-list">
              <article v-for="persona in personas" :key="persona.title" class="landing-persona-card">
                <div class="landing-persona-icon" v-html="persona.icon" />
                <div>
                  <h3>{{ persona.title }}</h3>
                  <p>{{ persona.description }}</p>
                </div>
              </article>
            </div>
          </div>

          <div class="landing-about-visual" aria-hidden="true">
            <div class="landing-about-orb" />
            <div class="landing-about-grid" />
            <div class="landing-workspace-card">
              <div class="landing-workspace-photo-shell">
                <img :src="workspaceImage" alt="TechReserve workspace preview" class="landing-workspace-photo" />
              </div>
              <div class="landing-dashboard-panel">
                <div class="landing-dashboard-header">
                  <span />
                  <span />
                  <span />
                </div>
                <div class="landing-dashboard-content">
                  <div class="landing-dashboard-chart landing-dashboard-chart-large" />
                  <div class="landing-dashboard-stats">
                    <div class="landing-dashboard-stat">
                      <strong>24</strong>
                      <span>Requests</span>
                    </div>
                    <div class="landing-dashboard-stat">
                      <strong>92%</strong>
                      <span>Ready</span>
                    </div>
                  </div>
                  <div class="landing-dashboard-chart landing-dashboard-chart-small" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="contact" class="landing-contact-section">
        <div class="landing-shell landing-contact">
          <div class="landing-contact-copy">
            <span class="landing-section-label">CONTACT US</span>
            <h2>We’re Here to Help</h2>
            <p>
              Have questions or need assistance? Reach out to us and we’ll get back to you.
            </p>
          </div>

          <div class="landing-contact-card">
            <article v-for="item in contactItems" :key="item.label" class="landing-contact-item">
              <div class="landing-contact-icon" v-html="item.icon" />
              <div>
                <h3>{{ item.label }}</h3>
                <p>{{ item.value }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>
    </main>

    <footer class="landing-footer">
      <div class="landing-shell landing-footer-inner">
        <img :src="logoPrimary" alt="TechReserve" class="landing-footer-logo" />
        <p>&copy; 2026 TechReserve. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import logoPrimary from '@/assets/TechReserve_LogoA.png';
import heroImage from '@/assets/221138d8-d7d9-4020-9907-bb1e218d2724.jpg';
import workspaceImage from '@/assets/01f7a5a9-fd6a-4259-9abc-84a378d1784b.jpg';

const router = useRouter();
const activeSection = ref('home');

const navItems = [
  { id: 'home', label: 'Home' },
  { id: 'about', label: 'About' },
  { id: 'contact', label: 'Contact' },
];

const featureCards = [
  {
    title: 'Reservations Made Easy',
    description: 'Create, manage, and track reservations in just a few clicks.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    title: 'Real-time Availability',
    description: 'Check live availability of equipment and venues.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 2l8 4.5v11L12 22l-8-4.5v-11L12 2Zm0 0v20M4 6.5l8 4.5 8-4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    title: 'Secure & Reliable',
    description: 'Role-based access and secure workflows you can trust.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3 5 6v5c0 5 3.4 8.5 7 10 3.6-1.5 7-5 7-10V6l-7-3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    title: 'Insights & Reports',
    description: 'Generate reports and visualize usage with ease.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 19h16M7 16V9M12 16V5M17 16v-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
];

const personas = [
  {
    title: 'For Administrator',
    description: 'Oversee operations, manage inventory, approve requests, and generate reports.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    title: 'For User',
    description: 'Browse availability, make reservations, and track request status with ease.',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-6.5 8a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
];

const contactItems = [
  {
    label: 'Email',
    value: 'datamstechreserve@gmail.com',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 6h16v12H4zM4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    label: 'Phone',
    value: '+63 912 345 6789',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M6.5 4.5h2.7l1.3 4.2-1.8 1.8a15 15 0 0 0 4.7 4.7l1.8-1.8 4.2 1.3v2.7A1.6 1.6 0 0 1 17.8 19 13.3 13.3 0 0 1 5 6.2 1.6 1.6 0 0 1 6.5 4.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
  {
    label: 'Address',
    value: 'FEU Institute of Technology, P. Paredes St., Sampaloc, Manila, Philippines',
    icon: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5a2.5 2.5 0 1 0-2.5-2.5 2.5 2.5 0 0 0 2.5 2.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `,
  },
];

function goToSignIn() {
  router.push({ name: ROUTE_NAMES.clerkLogin });
}

function scrollToSection(id) {
  activeSection.value = id;
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function syncActiveSection() {
  const offsets = navItems
    .map((item) => {
      const element = document.getElementById(item.id);
      if (!element) {
        return null;
      }

      const top = element.getBoundingClientRect().top + window.scrollY - 120;
      return { id: item.id, top };
    })
    .filter(Boolean);

  const currentScroll = window.scrollY;
  const current = offsets
    .slice()
    .reverse()
    .find((item) => currentScroll >= item.top);

  activeSection.value = current?.id || 'home';
}

onMounted(() => {
  syncActiveSection();
  window.addEventListener('scroll', syncActiveSection, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', syncActiveSection);
});
</script>

<style scoped>
@import './css/LandingPage.css';
</style>
