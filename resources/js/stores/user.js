import axios from "axios";
import { defineStore } from "pinia";
import { ref } from "vue";

export const useUserStore = defineStore("user", () => {
    const user = ref(null);
    const activeAgency = ref(null);

    async function getUser() {
        await axios
            .get("/user")
            .then((response) => {
                setUser(response.data);
            })
            .catch((error) => {
                console.log(error);
            });
    }

    function setUser(newUser) {
        user.value = newUser;

        if (user.value.agencies.length === 1) {
            activeAgency.value = user.value.agencies[0];
            return;
        }

        if (localStorage.getItem("activeAgency")) {
            let agency = JSON.parse(localStorage.getItem("activeAgency"));

            if (user.value.agencies.find((a) => a.id === agency.id)) {
                activeAgency.value = a;
            }
        }
    }

    function setAgency(agency) {
        activeAgency.value = agency;

        localStorage.setItem("activeAgency", JSON.stringify(agency));
    }

    function logout() {
        user.value = null;
        activeAgency.value = null;

        axios.post("/logout");
    }

    return { user, activeAgency, getUser, logout, setUser, setAgency };
});
