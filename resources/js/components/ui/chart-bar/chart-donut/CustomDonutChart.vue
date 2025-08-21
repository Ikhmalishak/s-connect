<script setup>
import { Donut } from "@unovis/ts";
import { VisDonut, VisSingleContainer } from "@unovis/vue";
import { useMounted } from "@vueuse/core";
import { computed, ref } from "vue";
import { cn } from "@/lib/utils";
import { ChartSingleTooltip, defaultColors } from "@/components/ui/chart";
import { List } from "lucide-vue-next";
import CustomTooltip from "@/Components/CustomTooltip.vue";

const props = defineProps({
    data: { type: Array, required: true },
    colors: { type: Array, required: false },
    index: { type: null, required: true },
    margin: {
        type: Object,
        required: false,
        default: () => ({ top: 0, bottom: 0, left: 0, right: 0 }),
    },
    showLegend: { type: Boolean, required: false, default: true },
    showTooltip: { type: Boolean, required: false, default: true },
    filterOpacity: { type: Number, required: false, default: 0.2 },
    category: { type: String, required: true },
    type: { type: String, required: false, default: "donut" },
    sortFunction: { type: Function, required: false, default: () => undefined },
    valueFormatter: { type: Function, required: false },
    customTooltip: { type: null, required: false },
    centerLabel: { type: String, required: false },
    centralSubLabel: { type: String, required: false },
    legendPosition: {
        type: String,
        required: false,
        default: "right",
    },
});

// Add emit definition
const emit = defineEmits(["central-click"]);

const valueFormatter = props.valueFormatter ?? ((tick) => `${tick}`);
const category = computed(() => props.category);
const index = computed(() => props.index);
const isMounted = useMounted();
const activeSegmentKey = ref();

const colors = computed(() =>
    props.colors?.length
        ? props.colors
        : defaultColors(
              props.data.filter((d) => d[props.category]).filter(Boolean).length
          )
);

const legendItems = computed(() =>
    props.data.map((item, i) => {
        let rawName = item[props.index];

        // Sanitize name
        if (rawName === "inbound-shipment/transfer") {
            rawName = "Driver Inbound-Ship/Transfer";
        }
        if (rawName === "outbound-shipment/transfer") {
            rawName = "Driver Outbound-Ship/Transfer";
        }

        return {
            name: rawName,
            color: colors.value[i],
            value: item[props.category],
            percentage: (
                (item[props.category] / totalValue.value) *
                100
            ).toFixed(1),
            inactive:
                activeSegmentKey.value !== undefined &&
                activeSegmentKey.value !== item[props.index],
        };
    })
);

const totalValue = computed(() =>
    props.data.reduce((prev, curr) => {
        return prev + curr[props.category];
    }, 0)
);

const handleLegendClick = (itemName) => {
    if (activeSegmentKey.value === itemName) {
        activeSegmentKey.value = undefined;
    } else {
        activeSegmentKey.value = itemName;
    }
};

const containerClass = computed(() => {
    const baseClass = "w-full flex items-center";
    switch (props.legendPosition) {
        case "bottom":
            return `${baseClass} flex-col`;
        case "left":
            return `${baseClass} flex-row-reverse`;
        default:
            return `${baseClass} flex-row`;
    }
});

const chartClass = computed(() => {
    switch (props.legendPosition) {
        case "bottom":
            return "w-full h-48 mb-4";
        case "left":
            return "flex-1 h-48 ml-4";
        default:
            return "flex-1 h-48 mr-4";
    }
});

const legendClass = computed(() => {
    switch (props.legendPosition) {
        case "bottom":
            return "w-full flex flex-wrap justify-center gap-4";
        case "left":
            return "flex flex-col gap-2 min-w-48";
        default:
            return "flex flex-col gap-2 min-w-48";
    }
});

function formatCategoryName(name) {
    return name.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

// Modified to emit event instead of managing modal
function handleCentralSubClick() {
    emit("central-click");
}
</script>

<template>
    <div
        :class="
            cn(
                'relative w-full h-48 flex flex-col items-end',
                $attrs.class ?? ''
            )
        "
    >
        <VisSingleContainer
            :style="{ height: isMounted ? '100%' : 'auto' }"
            :margin="{ left: 20, right: 20 }"
            :data="data"
        >
            <ChartSingleTooltip
                :selector="Donut.selectors.segment"
                :index="category"
                :items="legendItems"
                :value-formatter="valueFormatter"
                :custom-tooltip="customTooltip"
            />

            <VisDonut
                :value="(d) => d[category]"
                :sort-function="sortFunction"
                :color="colors"
                :arc-width="type === 'donut' ? 30 : 0"
                :show-background="true"
                :central-label="
                    type === 'donut'
                        ? props.centerLabel ?? valueFormatter(totalValue)
                        : ''
                "
                :centralSubLabel="''"
                :events="{
                    [Donut.selectors.segment]: {
                        click: (d, ev, i, elements) => {
                            if (d?.data?.[index] === activeSegmentKey) {
                                activeSegmentKey = undefined;
                                elements.forEach(
                                    (el) => (el.style.opacity = '1')
                                );
                            } else {
                                activeSegmentKey = d?.data?.[index];
                                elements.forEach(
                                    (el) =>
                                        (el.style.opacity = `${filterOpacity}`)
                                );
                                elements[i].style.opacity = '1';
                            }
                        },
                    },
                }"
            />

            <!-- Center clickable label overlay -->
            <div
                v-if="type === 'donut' && props.centralSubLabel === 'In'"
                class="absolute top-0 right-5 pointer-events-auto cursor-pointer text-lg text-black font-black transition"
            >
                {{ props.centralSubLabel ?? "" }}
            </div>

            <div
                v-else
                class="absolute top-0 right-0 pointer-events-auto cursor-pointer text-lg text-black font-black transition"
            >
                {{ props.centralSubLabel ?? "" }}
            </div>

            <div
                v-if="type === 'donut' && centralSubLabel === 'In'"
                class="absolute top-0 left-0 pointer-events-auto cursor-pointer text-lg text-black font-black transition mt-1"
                @click="handleCentralSubClick"
            >
                <CustomTooltip
                    text="Click to see more details"
                    position="bottom"
                    ><List class="w-6 h-6"
                /></CustomTooltip>
            </div>

            <slot />
        </VisSingleContainer>
    </div>

    <!-- Legend -->
    <div class="mt-2"></div>
    <div v-if="showLegend" :class="legendClass">
        <div
            v-for="item in legendItems"
            :key="item.name"
            :class="
                cn(
                    'flex items-center cursor-pointer transition-opacity duration-200 hover:opacity-80',
                    legendPosition === 'bottom'
                        ? 'flex-col text-center'
                        : 'justify-between',
                    item.inactive ? 'opacity-40' : 'opacity-100'
                )
            "
            @click="handleLegendClick(item.name)"
        >
            <div
                :class="
                    cn(
                        'flex items-center',
                        legendPosition === 'bottom' ? 'mb-1' : 'mr-2'
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'w-3 h-3 rounded-full mr-2',
                            legendPosition === 'bottom' ? 'mr-1' : 'mr-2'
                        )
                    "
                    :style="{ backgroundColor: item.color }"
                />
                <span
                    :class="
                        cn(
                            'text-sm font-medium',
                            legendPosition === 'bottom' ? 'text-xs' : 'text-sm'
                        )
                    "
                >
                    {{ formatCategoryName(item.name) }}
                </span>
            </div>

            <div
                :class="
                    cn(
                        'flex flex-col',
                        legendPosition === 'bottom'
                            ? 'items-center'
                            : 'items-end'
                    )
                "
            >
                <span class="text-sm font-semibold">{{
                    valueFormatter(item.value)
                }}</span>
            </div>
        </div>
    </div>
</template>
