import React, { useState } from "react";
import ReactDOM from "react-dom";

const CopyClipboard = ({ text }) => {
    const [isCopied, setIsCopied] = useState(false);
    const timeout = 1000;

    const copyText = async () => {
        if ("clipboard" in navigator)
            return await navigator.clipboard.writeText(text);
        else return document.execCommand("copy", true, text);
    };

    const handleClick = () => {
        copyText().then(() => {
            setIsCopied(true);
            setTimeout(() => {
                setIsCopied(false);
            }, timeout);
        });
    };

    return (
        <div className="CopyClipboard">
            <div className="row">
                <div className="col-auto">
                    <b>{text}</b>
                </div>
                <div className="col">
                    <button className="btn btn-sm btn-search-outline float-end" onClick={handleClick}>
                        <span>{isCopied ? "Copied!" : "Copy"}</span>
                    </button>
                </div>
            </div>
        </div>
    );
};

export default CopyClipboard;
