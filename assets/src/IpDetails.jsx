import {useState} from '@wordpress/element';

function IpDetails(props) {
    const [isShown, setIsShown] = useState(false);
    const [error, setError] = useState(null);
    const [details, setDetails] = useState(null);

    function loadAndShow() {


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
                    if (response.code !== '200') {
                        return Promise.reject(response.data.message);
                    }
                    return response;
                })
                .then(response => {
                    setDetails(response.data);
                    sessionStorage.setItem(props.address, JSON.stringify(response.data));
                    return response.data;
                })
                .catch((error) => {
                    setError(error);
                })
                .finally(() => setIsShown(true));

        }

    }

    function viewDetails() {
        if (error) {
            return (
                <ul>
                    <li>{error}</li>
                </ul>
            )
        } else {
            return (
                <ul>
                    <li><b>City:</b> {details.city}</li>
                    <li><b>Country:</b> {details.country_name}, { details.country_code }</li>
                    <li><b>Latitude:</b> {details.lat}</li>
                    <li><b>Longitude:</b> {details.lon}</li>
                    <li><b>Provider:</b> {details.isp}</li>
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
}

export default IpDetails;