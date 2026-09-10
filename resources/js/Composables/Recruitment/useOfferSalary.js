import { computed, unref } from "vue";

export const SALARY_COMPONENT_TYPES = {
    EARNING: "earning",
    DEDUCTION: "deduction",
    EMPLOYER_CONTRIBUTION: "employer_contribution",
};

export const SALARY_CALCULATION_TYPES = {
    FIXED: "fixed",
    PERCENTAGE: "percentage",
};

export const SALARY_FREQUENCIES = {
    MONTHLY: "monthly",
    QUARTERLY: "quarterly",
    HALF_YEARLY: "half_yearly",
    ANNUAL: "annual",
    ONE_TIME: "one_time",
};

const toNumber = (value) => {
    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed)
        ? parsed
        : 0;
};

const roundMoney = (value) => {
    return Math.round(
        (toNumber(value) + Number.EPSILON) * 100,
    ) / 100;
};

const monthlyMultiplier = (frequency) => {
    switch (frequency) {
        case SALARY_FREQUENCIES.MONTHLY:
            return 1;

        case SALARY_FREQUENCIES.QUARTERLY:
            return 1 / 3;

        case SALARY_FREQUENCIES.HALF_YEARLY:
            return 1 / 6;

        case SALARY_FREQUENCIES.ANNUAL:
            return 1 / 12;

        case SALARY_FREQUENCIES.ONE_TIME:
            return 0;

        default:
            return 1;
    }
};

const annualMultiplier = (frequency) => {
    switch (frequency) {
        case SALARY_FREQUENCIES.MONTHLY:
            return 12;

        case SALARY_FREQUENCIES.QUARTERLY:
            return 4;

        case SALARY_FREQUENCIES.HALF_YEARLY:
            return 2;

        case SALARY_FREQUENCIES.ANNUAL:
            return 1;

        case SALARY_FREQUENCIES.ONE_TIME:
            return 1;

        default:
            return 12;
    }
};

export default function useOfferSalary(
    salaryComponents,
) {
    const components = computed(() => {
        const value = unref(salaryComponents);

        return Array.isArray(value)
            ? value
            : [];
    });

    const componentMap = computed(() => {
        return new Map(
            components.value.map((component) => [
                component.row_key,
                component,
            ]),
        );
    });

    const calculateComponentAmount = (
        component,
        visitedRowKeys = new Set(),
    ) => {
        if (!component) {
            return 0;
        }

        if (
            component.calculation_type !==
            SALARY_CALCULATION_TYPES.PERCENTAGE
        ) {
            return roundMoney(component.amount);
        }

        if (
            visitedRowKeys.has(component.row_key)
        ) {
            return 0;
        }

        const nextVisited = new Set(
            visitedRowKeys,
        );

        nextVisited.add(component.row_key);

        const baseRowKey =
            component.percentage_of_row_key;

        if (!baseRowKey) {
            return roundMoney(component.amount);
        }

        const baseComponent =
            componentMap.value.get(baseRowKey);

        if (!baseComponent) {
            return 0;
        }

        const baseAmount =
            calculateComponentAmount(
                baseComponent,
                nextVisited,
            );

        return roundMoney(
            baseAmount *
                (
                    toNumber(
                        component.percentage,
                    ) / 100
                ),
        );
    };

    const calculateMonthlyAmount = (
        component,
    ) => {
        const amount =
            calculateComponentAmount(component);

        return roundMoney(
            amount *
                monthlyMultiplier(
                    component.frequency,
                ),
        );
    };

    const calculateAnnualAmount = (
        component,
    ) => {
        const amount =
            calculateComponentAmount(component);

        return roundMoney(
            amount *
                annualMultiplier(
                    component.frequency,
                ),
        );
    };

    const calculatedComponents = computed(
        () => {
            return components.value.map(
                (component) => ({
                    ...component,

                    calculated_amount:
                        calculateComponentAmount(
                            component,
                        ),

                    monthly_amount:
                        calculateMonthlyAmount(
                            component,
                        ),

                    annual_amount:
                        calculateAnnualAmount(
                            component,
                        ),
                }),
            );
        },
    );

    const monthlyGross = computed(() => {
        return roundMoney(
            calculatedComponents.value
                .filter(
                    (component) =>
                        component.component_type ===
                        SALARY_COMPONENT_TYPES.EARNING,
                )
                .reduce(
                    (total, component) =>
                        total +
                        component.monthly_amount,
                    0,
                ),
        );
    });

    const monthlyDeductions = computed(
        () => {
            return roundMoney(
                calculatedComponents.value
                    .filter(
                        (component) =>
                            component.component_type ===
                                SALARY_COMPONENT_TYPES.DEDUCTION &&
                            Boolean(
                                component.affects_in_hand,
                            ),
                    )
                    .reduce(
                        (total, component) =>
                            total +
                            component.monthly_amount,
                        0,
                    ),
            );
        },
    );

    const monthlyEmployerContributions =
        computed(() => {
            return roundMoney(
                calculatedComponents.value
                    .filter(
                        (component) =>
                            component.component_type ===
                            SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION,
                    )
                    .reduce(
                        (total, component) =>
                            total +
                            component.monthly_amount,
                        0,
                    ),
            );
        });

    const monthlyInHand = computed(() => {
        return roundMoney(
            monthlyGross.value -
                monthlyDeductions.value,
        );
    });

    const annualCtc = computed(() => {
        return roundMoney(
            calculatedComponents.value
                .filter((component) =>
                    [
                        SALARY_COMPONENT_TYPES.EARNING,
                        SALARY_COMPONENT_TYPES.EMPLOYER_CONTRIBUTION,
                    ].includes(
                        component.component_type,
                    ),
                )
                .reduce(
                    (total, component) =>
                        total +
                        component.annual_amount,
                    0,
                ),
        );
    });

    const annualGross = computed(() => {
        return roundMoney(
            calculatedComponents.value
                .filter(
                    (component) =>
                        component.component_type ===
                        SALARY_COMPONENT_TYPES.EARNING,
                )
                .reduce(
                    (total, component) =>
                        total +
                        component.annual_amount,
                    0,
                ),
        );
    });

    const annualDeductions = computed(() => {
        return roundMoney(
            calculatedComponents.value
                .filter(
                    (component) =>
                        component.component_type ===
                        SALARY_COMPONENT_TYPES.DEDUCTION &&
                        Boolean(
                            component.affects_in_hand,
                        ),
                )
                .reduce(
                    (total, component) =>
                        total +
                        component.annual_amount,
                    0,
                ),
        );
    });

    const formatCurrency = (
        value,
        currency = "INR",
    ) => {
        return new Intl.NumberFormat("en-IN", {
            style: "currency",
            currency,
            maximumFractionDigits: 2,
        }).format(toNumber(value));
    };

    return {
        calculatedComponents,

        monthlyGross,
        monthlyDeductions,
        monthlyEmployerContributions,
        monthlyInHand,

        annualGross,
        annualDeductions,
        annualCtc,

        calculateComponentAmount,
        calculateMonthlyAmount,
        calculateAnnualAmount,

        formatCurrency,
        roundMoney,
    };
}