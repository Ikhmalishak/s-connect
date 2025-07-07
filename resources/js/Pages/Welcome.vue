<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

// Reactive data
const currentSlide = ref(0);
const isVisible = ref({});

// Carousel data
const carouselItems = [
  {
    title: "Streamline Your Workflow",
    description: "Transform your business processes with our integrated digital platform",
    image: "https://images.unsplash.com/photo-1552664730-d307ca884978?w=800&h=400&fit=crop&crop=entropy&auto=format",
    color: "from-blue-500 to-cyan-500"
  },
  {
    title: "Digital Forms Made Easy",
    description: "Create, manage, and process forms with intuitive drag-and-drop interface",
    image: "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=400&fit=crop&crop=entropy&auto=format",
    color: "from-purple-500 to-pink-500"
  },
  {
    title: "Real-time Collaboration",
    description: "Connect your team and enhance productivity with seamless communication",
    image: "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&h=400&fit=crop&crop=entropy&auto=format",
    color: "from-green-500 to-teal-500"
  }
];

// Features data
const features = [
  {
    icon: "🏢",
    title: "Enterprise Ready",
    description: "Built for businesses of all sizes with scalable architecture"
  },
  {
    icon: "👥",
    title: "Team Collaboration",
    description: "Seamless integration across departments and workflows"
  },
  {
    icon: "⚡",
    title: "Lightning Fast",
    description: "Optimized performance for maximum productivity"
  },
  {
    icon: "🛡️",
    title: "Secure & Reliable",
    description: "Enterprise-grade security with 99.9% uptime guarantee"
  }
];

let carouselTimer = null;
let observer = null;

// Methods
const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % carouselItems.length;
};

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + carouselItems.length) % carouselItems.length;
};

const goToSlide = (index) => {
  currentSlide.value = index;
};

// Lifecycle hooks
onMounted(() => {
  // Auto-advance carousel
  carouselTimer = setInterval(() => {
    nextSlide();
  }, 5000);

  // Intersection Observer for animations
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          isVisible.value = {
            ...isVisible.value,
            [entry.target.id]: true
          };
        }
      });
    },
    { threshold: 0.1 }
  );

  setTimeout(() => {
    const elements = document.querySelectorAll('[data-animate]');
    elements.forEach((el) => observer.observe(el));
  }, 100);
});

onUnmounted(() => {
  if (carouselTimer) {
    clearInterval(carouselTimer);
  }
  if (observer) {
    observer.disconnect();
  }
});
</script>

<template>
  <div class="bg-white min-h-screen">
    <!-- Header -->
    <nav class="bg-white/95 backdrop-blur-md border-b shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-2 group cursor-pointer">
          <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
            <span class="text-white font-bold text-sm">S</span>
          </div>
          <span class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
            S Connect
          </span>
        </div>

        <div class="hidden md:flex space-x-4">
          <a href="#" class="px-4 py-2 text-gray-700 hover:text-blue-600 transition-colors hover:bg-blue-50 rounded-lg">
            Home
          </a>
          <a href="#" class="px-4 py-2 text-gray-700 hover:text-blue-600 transition-colors hover:bg-blue-50 rounded-lg">
            Forms
          </a>
          <a href="/login" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:from-blue-700 hover:to-cyan-700 transition-all duration-300 transform hover:scale-105">
            Login
          </a>
        </div>
      </div>
    </nav>

    <!-- Hero Section with Carousel -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-blue-50"></div>
      
      <!-- Carousel -->
      <div class="relative w-full max-w-6xl mx-auto px-4 z-10">
        <div class="relative h-96 rounded-2xl overflow-hidden shadow-2xl">
          <div
            v-for="(item, index) in carouselItems"
            :key="index"
            class="absolute inset-0 transition-all duration-700 transform"
            :class="{
              'translate-x-0 opacity-100': index === currentSlide,
              '-translate-x-full opacity-0': index < currentSlide,
              'translate-x-full opacity-0': index > currentSlide
            }"
          >
            <div class="absolute inset-0 bg-gradient-to-r opacity-90" :class="item.color"></div>
            <img 
              :src="item.image" 
              :alt="item.title"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="text-center text-white p-8">
                <h1 class="text-4xl md:text-6xl font-bold mb-4 animate-pulse">
                  {{ item.title }}
                </h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">
                  {{ item.description }}
                </p>
                <button class="bg-white text-gray-800 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition-colors duration-300 transform hover:scale-105 flex items-center space-x-2 mx-auto">
                  <span>Get Started</span>
                  <span class="text-lg">→</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Carousel Controls -->
        <button
          @click="prevSlide"
          class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-3 rounded-full shadow-lg transition-all duration-300 transform hover:scale-110"
        >
          <span class="text-xl">‹</span>
        </button>
        <button
          @click="nextSlide"
          class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-3 rounded-full shadow-lg transition-all duration-300 transform hover:scale-110"
        >
          <span class="text-xl">›</span>
        </button>

        <!-- Carousel Indicators -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
          <button
            v-for="(_, index) in carouselItems"
            :key="index"
            @click="goToSlide(index)"
            class="w-3 h-3 rounded-full transition-all duration-300"
            :class="index === currentSlide ? 'bg-white' : 'bg-white/50'"
          />
        </div>
      </div>

      <!-- Floating Elements -->
      <div class="absolute top-20 left-10 w-20 h-20 bg-blue-200 rounded-full opacity-20 animate-bounce"></div>
      <div class="absolute bottom-20 right-10 w-16 h-16 bg-cyan-200 rounded-full opacity-20 animate-bounce" style="animation-delay: 1s"></div>
      <div class="absolute top-1/2 left-20 w-12 h-12 bg-purple-200 rounded-full opacity-20 animate-bounce" style="animation-delay: 2s"></div>
    </section>

    <!-- Company Background Section -->
    <section 
      id="about" 
      data-animate 
      class="py-20 transition-all duration-1000"
      :class="isVisible.about ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
    >
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            About <span class="text-blue-600">S Connect</span>
          </h2>
          <div class="w-24 h-1 bg-gradient-to-r from-blue-600 to-cyan-600 mx-auto mb-8"></div>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Revolutionizing business operations through intelligent digital transformation
          </p>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center">
          <div class="space-y-6">
            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 p-8 rounded-2xl">
              <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Mission</h3>
              <p class="text-gray-700 leading-relaxed">
                S Connect is an integrated internal system designed to digitize and streamline all company processes. 
                From form submissions to workflow management, our goal is to make your work more efficient and organized.
              </p>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-8 rounded-2xl">
              <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Vision</h3>
              <p class="text-gray-700 leading-relaxed">
                In the first phase, we focus on enabling digital request forms and providing a modern, 
                user-friendly interface for employees. We're building the future of workplace productivity.
              </p>
            </div>

            <div class="space-y-3">
              <div class="flex items-center space-x-4">
                <span class="w-6 h-6 text-green-500 text-xl">✓</span>
                <span class="text-gray-700">99.9% Uptime Guarantee</span>
              </div>
              <div class="flex items-center space-x-4">
                <span class="w-6 h-6 text-green-500 text-xl">✓</span>
                <span class="text-gray-700">24/7 Customer Support</span>
              </div>
              <div class="flex items-center space-x-4">
                <span class="w-6 h-6 text-green-500 text-xl">✓</span>
                <span class="text-gray-700">Enterprise-Grade Security</span>
              </div>
            </div>
          </div>

          <div class="relative">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl p-8 text-white transform hover:scale-105 transition-transform duration-300">
              <div class="grid grid-cols-2 gap-6">
                <div class="text-center">
                  <div class="text-3xl font-bold mb-2">500+</div>
                  <div class="text-blue-100">Companies</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold mb-2">10K+</div>
                  <div class="text-blue-100">Users</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold mb-2">99.9%</div>
                  <div class="text-blue-100">Uptime</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold mb-2">24/7</div>
                  <div class="text-blue-100">Support</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section 
      id="features" 
      data-animate 
      class="py-20 bg-gray-50 transition-all duration-1000"
      :class="isVisible.features ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
    >
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Why Choose <span class="text-blue-600">S Connect</span>
          </h2>
          <div class="w-24 h-1 bg-gradient-to-r from-blue-600 to-cyan-600 mx-auto"></div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div
            v-for="(feature, index) in features"
            :key="index"
            class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 group"
          >
            <div class="text-4xl mb-6 transform group-hover:scale-110 transition-transform duration-300">
              {{ feature.icon }}
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-cyan-600">
      <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-white mb-6">
          Ready to Transform Your Business?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
          Join thousands of companies already using S Connect to streamline their operations
        </p>
        <button class="bg-white text-blue-600 px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition-colors duration-300 transform hover:scale-105 inline-flex items-center space-x-2">
          <span>Start Your Free Trial</span>
          <span class="text-lg">→</span>
        </button>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="flex items-center justify-center space-x-2 mb-4">
          <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">S</span>
          </div>
          <span class="text-xl font-bold">S Connect</span>
        </div>
        <p class="text-gray-400">© 2025 S Connect. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.animate-bounce {
  animation: bounce 1.5s infinite;
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translate3d(0,0,0);
  }
  40%, 43% {
    transform: translate3d(0,-30px,0);
  }
  70% {
    transform: translate3d(0,-15px,0);
  }
  90% {
    transform: translate3d(0,-4px,0);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>