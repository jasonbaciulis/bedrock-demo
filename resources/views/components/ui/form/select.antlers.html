{{ if searchable }}
    {{ partial:components/ui/form/combobox }}
{{ else }}
    <select
        x-model="{{ model }}"
        :class="{
            'text-muted-foreground': !{{ model }}
        }"
        id="{{ id }}"
        name="{{ handle }}{{ multiple ?= "[]" }}"
        :aria-invalid="form.invalid('{{ handle }}')"
        {{ if instructions }}
            :aria-describedby="form.invalid('{{ handle }}') ? '{{ id }}-error' : '{{ id }}-instructions'"
        {{ else }}
            :aria-describedby="form.invalid('{{ handle }}') ? '{{ id }}-error' : undefined"
        {{ /if }}
        {{ multiple | attribute:multiple }}
        @change="form.validate('{{ handle }}')"
    >
        {{ unless multiple }}
            <option value :selected="!{{ model }} ? true : false" disabled>
                Select {{ handle | deslugify }}…
            </option>
        {{ /unless }}
        {{ foreach:options as="option|label" }}
            <option value="{{ option }}">
                {{ label }}
            </option>
        {{ /foreach:options }}
    </select>
{{ /if }}
