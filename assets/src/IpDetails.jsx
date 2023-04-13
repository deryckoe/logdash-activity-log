import {useState} from '@wordpress/element';

const IpDetails = (props) => {
    const [isShown, setIsShown] = useState(false);
    const [error, setError] = useState(null);
    const [details, setDetails] = useState(null);
    const loadAndShow = () => {

        if (sessionStorage.getItem(props.address)) {

            const storedDetails = sessionStorage.getItem(props.address);
            setDetails(JSON.parse(storedDetails));
            setIsShown(true);

        } else {
            fetch(wpApiSettings.root + 'logdash/v1/ip/' + props.address, {
                method: 'get',
                mode: 'cors',
                headers: {
                    'Access-Control-Allow-Origin': '*',
                    'X-WP-Nonce': wpApiSettings.nonce
                }
            })
                .then(response => response.json())
                .then(response => {
                    if (response.code === 'rest_forbidden') {
                        return Promise.reject(response);
                    }
                    if (response.code) {
                        return Promise.reject({code: 'error', message: 'There is an error.'});
                    }
                    return response;
                })
                .then(data => {
                    setDetails(data);
                    sessionStorage.setItem(props.address, JSON.stringify(data));
                    return data;
                })
                .catch((response) => {
                    setError(response.message);
                })
                .finally(() => setIsShown(true));

        }

    }

    const viewDetails = () => {
        if (error) {
            return (
                <ul>
                    <li>{error}</li>
                </ul>
            )
        } else {
            return (
                <ul>
                    <li>
                        <b>City:</b> {details.city.names.en}, {details.subdivisions[0].names.en}, {details.subdivisions[0].iso_code}
                    </li>
                    <li><b>Country:</b> {details.country.names.en}, {details.country.is_code}</li>
                    <li><b>Latitude:</b> {details.location.latitude}</li>
                    <li><b>Longitude:</b> {details.location.longitude}</li>
                    <li><b>Provider:</b> {details.traits.isp}</li>
                </ul>
            )
        }
    }

    return (
        <div
            onMouseLeave={() => setIsShown(false)}>
            <div onClick={loadAndShow} className={'label'}>
                <span className={'ip'}>{props.address}</span>
                <span className={'more'}><svg viewBox="0 0 512 512" width={14}><path
                    d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336c-13.3 0-24 10.7-24 24s10.7 24 24 24h80c13.3 0 24-10.7 24-24s-10.7-24-24-24h-8V248c0-13.3-10.7-24-24-24H216c-13.3 0-24 10.7-24 24s10.7 24 24 24h24v64H216zm40-144a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"/></svg></span>
            </div>
            {isShown && (
                <div className={'details'}>
                    <h2>Details for IP: {props.address}</h2>
                    {viewDetails()}
                </div>
            )}
        </div>
    );
};
export default IpDetails;