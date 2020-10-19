import axios from "../../../axios/index.js";
import store from "../../../../store/store.js";

// Token Refresh
let isAlreadyFetchingAccessToken = false;
let subscribers = [];

function onAccessTokenFetched(access_token) {
    subscribers = subscribers.filter(callback => callback(access_token));
}

function addSubscriber(callback) {
    subscribers.push(callback);
}

export default {
    init() {
        axios.interceptors.response.use(
            function (response) {
                return response;
            },
            function (error) {
                // const { config, response: { status } } = error
                const { config, response } = error;
                const originalRequest = config;

                // if (status === 401) {
                if (response && response.status === 401) {
                    if (!isAlreadyFetchingAccessToken) {
                        isAlreadyFetchingAccessToken = true;
                        store
                            .dispatch("auth/fetchAccessToken")
                            .then(access_token => {
                                isAlreadyFetchingAccessToken = false;
                                onAccessTokenFetched(access_token);
                            });
                    }

                    const retryOriginalRequest = new Promise(resolve => {
                        addSubscriber(access_token => {
                            originalRequest.headers.Authorization = `Bearer ${access_token}`;
                            resolve(axios(originalRequest));
                        });
                    });
                    return retryOriginalRequest;
                }
                return Promise.reject(error);
            }
        );
    },
    checkUsernamePwdBeforeLogin(login, pwd) {
        return axios.post("/api/auth/checkUsernamePwdBeforeLogin", {
            login: login,
            password: pwd
        });
    },
    login(login, pwd) {
        return axios.post("/api/auth/login", {
            login,
            password: pwd
        });
    },
    registerUser(
        firstname,
        lastname,
        email,
        pwd,
        c_password,
        company_name,
        isTermsConditionAccepted
    ) {
        return axios.post("/api/auth/register", {
            firstname: firstname,
            lastname: lastname,
            email,
            password: pwd,
            c_password: c_password,
            company_name: company_name,
            isTermsConditionAccepted: isTermsConditionAccepted
        });
    },
    registerUserWithToken(
        firstname,
        lastname,
        email,
        pwd,
        c_password,
        company_name,
        isTermsConditionAccepted,
        token
    ) {
        return axios.post("/api/auth/register/" + token, {
            firstname: firstname,
            lastname: lastname,
            email: email,
            password: pwd,
            c_password: c_password,
            company_name: company_name,
            isTermsConditionAccepted: isTermsConditionAccepted
        });
    },
    refreshToken() {
        return axios.post("/api/auth/refresh-token", {
            token: localStorage.getItem("token")
        });
    },
    forgotPassword(email) {
        return axios.post("/api/auth/forget", { email: email });
    },
    resetPassword(email, pwd, c_password, token) {
        return axios.post("/api/auth/reset/password", {
            email: email,
            password: pwd,
            password_confirmation: c_password,
            token: token
        });
    },
    updatePassword(user_id, pwd) {
        return axios.post("/api/auth/updatePasswordBeforeLogin", {
            user_id: user_id,
            new_password: pwd,
        });
    },
    logout() {
        return axios.get("/api/auth/logout");
    },
    verify(email) {
        return axios.post("/api/auth/email/resend", { email: email });
    }
};
