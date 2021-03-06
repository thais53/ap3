const errorMessage = {
    attributes: {
        name: "nom",
        siret: "SIRET",
        code: "n° de client",
        type: "type",
        contact_firstname: "prénom",
        contact_lastname: "nom",
        contact_function: "fonction",
        contact_email: "émail",
        contact_tel1: "téléphone fixe",
        contact_tel2: "téléphone mobile",
        street_number: "n° de rue",
        street_name: "rue",
        postal_code: "code postal",
        city: "ville",
        country: "pays",
        start_date: "date de début",
        end_date: "date de fin"
    },
    custom: {
        contact_tel1: {
            required: "Au moins un numéro de téléphone est obligatoire"
        },
        contact_tel2: {
            required: "Au moins un numéro de téléphone est obligatoire"
        }
    }
};

export default errorMessage;
